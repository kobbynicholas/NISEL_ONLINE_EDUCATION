<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/* =========================================================
   DATABASE
========================================================= */

require __DIR__ . "/config/db.php";


/* =========================================================
   PHPMailer
========================================================= */

require_once __DIR__ . "/PHPMailer/src/Exception.php";
require_once __DIR__ . "/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/PHPMailer/src/SMTP.php";


/* =========================================================
   SESSION
========================================================= */

session_start();


/* =========================================================
   PAYSTACK SECRET KEY
========================================================= */

/*
 * IMPORTANT:
 *
 * Put your actual Paystack SECRET KEY here locally.
 *
 * Do not publish your secret key online.
 */

$secretKey = "YOUR_PAYSTACK_SECRET_KEY";


/* =========================================================
   GET PAYSTACK REFERENCE
========================================================= */

$reference =
    trim(
        $_GET['reference'] ?? ''
    );


if ($reference === '') {

    showError(
        "Payment reference not found."
    );

    exit;

}


/* =========================================================
   VERIFY PAYMENT WITH PAYSTACK
========================================================= */

$url =
    "https://api.paystack.co/transaction/verify/"
    .
    urlencode(
        $reference
    );


$ch =
    curl_init();


curl_setopt(
    $ch,
    CURLOPT_URL,
    $url
);


curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);


curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [

        "Authorization: Bearer "
        .
        $secretKey,

        "Cache-Control: no-cache"

    ]
);


curl_setopt(
    $ch,
    CURLOPT_TIMEOUT,
    30
);


$response =
    curl_exec($ch);


if (
    curl_errno($ch)
) {

    $error =
        curl_error($ch);

    curl_close($ch);


    showError(
        "Unable to contact Paystack: "
        .
        $error
    );

    exit;

}


$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close($ch);


/* =========================================================
   DECODE RESPONSE
========================================================= */

$result =
    json_decode(
        $response,
        true
    );


/* =========================================================
   CHECK PAYSTACK RESPONSE
========================================================= */

if (
    $httpCode !== 200
    ||
    !isset(
        $result['status']
    )
    ||
    $result['status'] !== true
) {

    showError(
        "Paystack could not verify this payment."
    );

    exit;

}


/* =========================================================
   CHECK TRANSACTION STATUS
========================================================= */

if (
    !isset(
        $result['data']['status']
    )
    ||
    $result['data']['status'] !== 'success'
) {

    showError(
        "The payment was not successful."
    );

    exit;

}


/* =========================================================
   PAYSTACK DATA
========================================================= */

$paymentData =
    $result['data'];


$paystackReference =
    $paymentData['reference']
    ??
    $reference;


$paymentStatus =
    $paymentData['status']
    ??
    '';


$paymentChannel =
    $paymentData['channel']
    ??
    'Unknown';


$amountPaid =
    (
        (float)(
            $paymentData['amount']
            ?? 0
        )
        / 100
    );


/* =========================================================
   EXPECTED PRICE
========================================================= */

$expectedAmount =
    1000;


/* =========================================================
   VERIFY AMOUNT
========================================================= */

if (
    $amountPaid < $expectedAmount
) {

    showError(

        "The payment amount does not match "
        .
        "the NISEL lesson package price."

    );

    exit;

}


/* =========================================================
   GET BOOKING REFERENCE
========================================================= */

/*
 * The new payment.php uses the booking reference
 * itself as the Paystack reference.
 *
 * We also check metadata as a fallback.
 */

$bookingReference =
    $reference;


if (
    isset(
        $paymentData['metadata']
    )
    &&
    is_array(
        $paymentData['metadata']
    )
    &&
    !empty(
        $paymentData['metadata']
        ['booking_reference']
    )
) {

    $bookingReference =
        $paymentData['metadata']
        ['booking_reference'];

}


/* =========================================================
   FIND BOOKING
========================================================= */

try {

    $stmt =
        $pdo->prepare("

            SELECT *

            FROM bookings

            WHERE booking_reference = ?

            LIMIT 1

        ");


    $stmt->execute([

        $bookingReference

    ]);


    $booking =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    showError(

        "Unable to retrieve the booking."

    );

    exit;

}


/* =========================================================
   BOOKING NOT FOUND
========================================================= */

if (!$booking) {

    showError(

        "The booking associated with this payment "
        .
        "could not be found."

    );

    exit;

}


/* =========================================================
   BOOKING DETAILS
========================================================= */

$bookingId =
    $booking['id'];


$studentName =
    $booking['student_name']
    ??
    'Student';


$email =
    $booking['email']
    ??
    '';


$phone =
    $booking['phone']
    ??
    '';


$subject =
    $booking['subjects']
    ??
    '';


$curriculum =
    $booking['curriculum']
    ??
    '';


$classYear =
    $booking['class_year']
    ??
    '';


/* =========================================================
   UPDATE BOOKING
========================================================= */

try {

    $pdo->beginTransaction();


    /* =====================================================
       UPDATE BOOKING PAYMENT
    ===================================================== */

    $update =
        $pdo->prepare("

            UPDATE bookings

            SET

                payment_status = 'Paid',

                paystack_reference = ?,

                amount = ?,

                assignment_status =
                    COALESCE(
                        NULLIF(
                            assignment_status,
                            ''
                        ),
                        'Pending'
                    )

            WHERE id = ?

        ");


    $update->execute([

        $paystackReference,

        $amountPaid,

        $bookingId

    ]);


    /* =====================================================
       CREATE PAYMENT RECORD
    ===================================================== */

    /*
     * We check whether a payment record already exists
     * using the Paystack reference.
     */

    $paymentExists = false;


    try {

        $checkPayment =
            $pdo->prepare("

                SELECT id

                FROM payments

                WHERE paystack_reference = ?

                LIMIT 1

            ");


        $checkPayment->execute([

            $paystackReference

        ]);


        $paymentExists =
            (bool)$checkPayment->fetch(
                PDO::FETCH_ASSOC
            );


    } catch (PDOException $e) {

        /*
         * If the payments table does not have
         * paystack_reference, we don't stop the
         * successful booking update.
         */

        $paymentExists = false;

    }


    /* =====================================================
       INSERT PAYMENT
    ===================================================== */

    if (!$paymentExists) {

        try {

            /*
             * This matches the payment fields used
             * by the NISEL payment records page.
             */

            $insertPayment =
                $pdo->prepare("

                    INSERT INTO payments (

                        student_name,

                        amount,

                        payment_method,

                        status,

                        payment_date,

                        paystack_reference

                    )

                    VALUES (

                        ?,
                        ?,
                        ?,
                        'Paid',
                        NOW(),
                        ?

                    )

                ");


            $insertPayment->execute([

                $studentName,

                $amountPaid,

                $paymentChannel,

                $paystackReference

            ]);


        } catch (PDOException $e) {

            /*
             * If paystack_reference does not exist
             * in your payments table, try the simpler
             * version.
             */

            try {

                $insertPayment =
                    $pdo->prepare("

                        INSERT INTO payments (

                            student_name,

                            amount,

                            payment_method,

                            status,

                            payment_date

                        )

                        VALUES (

                            ?,
                            ?,
                            ?,
                            'Paid',
                            NOW()

                        )

                    ");


                $insertPayment->execute([

                    $studentName,

                    $amountPaid,

                    $paymentChannel

                ]);


            } catch (PDOException $e2) {

                /*
                 * We don't cancel the booking because
                 * the Paystack payment itself has already
                 * been successfully verified.
                 */

            }

        }

    }


    $pdo->commit();


} catch (PDOException $e) {

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();

    }


    showError(

        "Unable to update your booking after payment."

    );

    exit;

}


/* =========================================================
   CREATE 8-LESSON SCHEDULE
========================================================= */

/*
 * Every subject booking receives:
 *
 * 8 lessons
 * 2 lessons per week
 *
 * The first lesson is scheduled from the booking
 * creation date.
 *
 * Admin/teacher can later adjust the actual dates.
 */


/* =========================================================
   SCHEDULE DIRECTORY
========================================================= */

$dataDirectory =
    __DIR__ . "/data";


if (
    !is_dir(
        $dataDirectory
    )
) {

    mkdir(
        $dataDirectory,
        0777,
        true
    );

}


/* =========================================================
   SCHEDULE FILE
========================================================= */

$scheduleFile =
    $dataDirectory
    .
    "/schedules.json";


if (
    !file_exists(
        $scheduleFile
    )
) {

    file_put_contents(

        $scheduleFile,

        json_encode(
            [],
            JSON_PRETTY_PRINT
        )

    );

}


/* =========================================================
   READ EXISTING SCHEDULES
========================================================= */

$schedules = [];


$json =
    file_get_contents(
        $scheduleFile
    );


if (
    $json !== false
    &&
    trim($json) !== ''
) {

    $decoded =
        json_decode(
            $json,
            true
        );


    if (
        is_array(
            $decoded
        )
    ) {

        $schedules =
            $decoded;

    }

}


/* =========================================================
   CHECK WHETHER 8 LESSONS ALREADY EXIST
========================================================= */

$existingLessons = 0;


foreach (
    $schedules as $lesson
) {

    if (
        isset(
            $lesson['booking_id']
        )
        &&
        (string)$lesson['booking_id']
        ===
        (string)$bookingId
    ) {

        $existingLessons++;

    }

}


/* =========================================================
   CREATE 8 LESSONS
========================================================= */

if (
    $existingLessons === 0
) {


    /*
     * We create two lessons per week.
     *
     * For now, the dates are generated automatically.
     *
     * Admin can later modify the schedule.
     */

    $startDate =
        new DateTime();


    for (
        $i = 1;
        $i <= 8;
        $i++
    ) {


        /*
         * Every two lessons represent one week.
         */

        $week =
            ceil(
                $i / 2
            );


        /*
         * Lesson 1 and 2:
         * Week 1
         *
         * Lesson 3 and 4:
         * Week 2
         *
         * etc.
         *
         * We use 3-day spacing for the two weekly
         * lessons.
         */

        if (
            $i % 2 === 1
        ) {

            $daysToAdd =
                (
                    $week - 1
                )
                *
                7;

        } else {

            $daysToAdd =
                (
                    (
                        $week - 1
                    )
                    *
                    7
                )
                +
                3;

        }


        $lessonDate =
            clone $startDate;


        $lessonDate->modify(

            "+"
            .
            $daysToAdd
            .
            " days"

        );


        /*
         * Default time.
         *
         * Admin/teacher can later change this.
         */

        $lessonTime =
            "16:00";


        /* =============================================
           FIND TEACHER
        ============================================== */

        $teacherId =
            $booking['teacher_id']
            ??
            '';


        $teacherName =
            $booking['teacher_name']
            ??
            'Not Assigned';


        /* =============================================
           ADD LESSON
        ============================================== */

        $schedules[] = [

            "id" =>
                uniqid(
                    "lesson_",
                    true
                ),

            "booking_id" =>
                $bookingId,

            "booking_reference" =>
                $bookingReference,

            "student_name" =>
                $studentName,

            "student_email" =>
                $email,

            "teacher_id" =>
                $teacherId,

            "teacher_name" =>
                $teacherName,

            "subjects" =>
                $subject,

            "curriculum" =>
                $curriculum,

            "class_year" =>
                $classYear,

            "lesson_number" =>
                $i,

            "week" =>
                $week,

            "lesson_date" =>
                $lessonDate->format(
                    "Y-m-d"
                ),

            "lesson_time" =>
                $lessonTime,

            "lesson_status" =>
                "Scheduled",

            "created_at" =>
                date(
                    "Y-m-d H:i:s"
                )

        ];

    }


    /* =====================================================
       SAVE SCHEDULES
    ===================================================== */

    file_put_contents(

        $scheduleFile,

        json_encode(
            $schedules,
            JSON_PRETTY_PRINT
        )

    );

}


/* =========================================================
   SEND CONFIRMATION EMAIL
========================================================= */

if (
    !empty($email)
) {

    $mail =
        new PHPMailer(true);


    try {

        $mail->isSMTP();


        $mail->Host =
            'smtp.gmail.com';


        $mail->SMTPAuth =
            true;


        /*
         * Use your actual Gmail account here.
         */

        $mail->Username =
            'YOUR_GMAIL_ADDRESS';


        /*
         * Use your Gmail APP PASSWORD here.
         *
         * Do not use your normal Gmail password.
         */

        $mail->Password =
            'YOUR_GMAIL_APP_PASSWORD';


        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;


        $mail->Port =
            587;


        $mail->setFrom(

            'YOUR_GMAIL_ADDRESS',

            'NISEL ONLINE EDUCATION'

        );


        $mail->addAddress(

            $email,

            $studentName

        );


        $mail->isHTML(true);


        $mail->Subject =
            'NISEL ONLINE EDUCATION - Payment Confirmation';


        $mail->Body = '

        <div
            style="
                font-family:Arial;
                max-width:650px;
                margin:auto;
                padding:20px;
            "
        >

            <h2
                style="
                    color:#003366;
                "
            >

                NISEL ONLINE EDUCATION

            </h2>


            <h3>

                Payment Successful

            </h3>


            <p>

                Dear
                <strong>
                    '
                    .
                    htmlspecialchars(
                        $studentName
                    )
                    .
                    '
                </strong>,

            </p>


            <p>

                Your payment has been successfully
                verified.

            </p>


            <hr>


            <p>

                <strong>
                    Booking Reference:
                </strong>

                '
                .
                htmlspecialchars(
                    $bookingReference
                )
                .
                '

            </p>


            <p>

                <strong>
                    Subject:
                </strong>

                '
                .
                htmlspecialchars(
                    $subject
                )
                .
                '

            </p>


            <p>

                <strong>
                    Curriculum:
                </strong>

                '
                .
                htmlspecialchars(
                    $curriculum
                )
                .
                '

            </p>


            <p>

                <strong>
                    Class:
                </strong>

                '
                .
                htmlspecialchars(
                    $classYear
                )
                .
                '

            </p>


            <p>

                <strong>
                    Package:
                </strong>

                8 lessons per month
                (2 lessons per week)

            </p>


            <p>

                <strong>
                    Amount Paid:
                </strong>

                GHS
                '
                .
                number_format(
                    $amountPaid,
                    2
                )
                .
                '

            </p>


            <p>

                <strong>
                    Payment Method:
                </strong>

                '
                .
                htmlspecialchars(
                    $paymentChannel
                )
                .
                '

            </p>


            <p>

                Your teacher will be assigned by
                NISEL ONLINE EDUCATION.

            </p>


            <p>

                Thank you for choosing
                <strong>
                    NISEL ONLINE EDUCATION
                </strong>.

            </p>


            <hr>


            <p>

                NISEL ONLINE EDUCATION<br>

                Empowering Learners Worldwide

            </p>

        </div>

        ';


        $mail->send();


    } catch (Exception $e) {

        /*
         * Email failure should NOT make the
         * successful payment appear as failed.
         */

    }

}


/* =========================================================
   SAVE SUCCESS SESSION
========================================================= */

$_SESSION[
    'last_payment'
] = [

    "booking_reference" =>
        $bookingReference,

    "student_name" =>
        $studentName,

    "email" =>
        $email,

    "subject" =>
        $subject,

    "curriculum" =>
        $curriculum,

    "class_year" =>
        $classYear,

    "amount" =>
        $amountPaid,

    "payment_method" =>
        $paymentChannel

];


/* =========================================================
   REDIRECT TO SUCCESS PAGE
========================================================= */

header(

    "Location: success.php?booking="
    .
    urlencode(
        $bookingReference
    )

);

exit;


/* =========================================================
   ERROR FUNCTION
========================================================= */

function showError(
    string $message
): void
{

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
Payment Failed |
NISEL ONLINE EDUCATION
</title>


<style>

body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #fff4f4;

    text-align: center;

    padding: 50px 20px;

}


.box {

    background: white;

    max-width: 650px;

    margin: auto;

    padding: 40px;

    border-radius: 12px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.12);

}


h1 {

    color: #c82333;

}


p {

    color: #555;

    line-height: 1.6;

}


.button {

    display: inline-block;

    margin-top: 20px;

    padding: 13px 25px;

    background: #003366;

    color: white;

    text-decoration: none;

    border-radius: 6px;

}

</style>

</head>


<body>


<div class="box">


<h1>

❌ Payment Verification Failed

</h1>


<p>

<?php

echo htmlspecialchars(
    $message
);

?>

</p>


<a
    href="student/book_lesson.php"
    class="button"
>

    Return to Booking

</a>


</div>


</body>

</html>

<?php

}
?>
