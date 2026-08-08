/* =========================================================
   APPROVE / REJECT APPLICATION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? "";

    /* =====================================================
       APPROVE APPLICATION
    ===================================================== */

    if ($action === "approve") {

        /* Get the application again */

        $stmt = $conn->prepare("
            SELECT *
            FROM teacher_applications
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $application_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {

            $message = "Teacher application not found.";
            $message_type = "error";

        } else {

            $application = $result->fetch_assoc();

            $stmt->close();


            /* =============================================
               CHECK WHETHER ALREADY APPROVED
            ============================================= */

            if ($application['application_status'] === "Approved") {

                $message =
                    "This teacher application has already been approved.";

                $message_type = "error";

            } else {


                /* =============================================
                   GET TEACHER INFORMATION
                ============================================= */

                $teacher_name =
                    trim($application['full_name']);

                $email =
                    trim($application['email']);

                $phone =
                    trim($application['phone']);

                $subjects =
                    trim($application['subjects']);

                $curriculum =
                    trim($application['curricula']);


                /* =============================================
                   CHECK IF EMAIL ALREADY EXISTS
                ============================================= */

                $check = $conn->prepare("
                    SELECT teacher_id
                    FROM teachers
                    WHERE email = ?
                    LIMIT 1
                ");

                $check->bind_param(
                    "s",
                    $email
                );

                $check->execute();

                $existing =
                    $check->get_result();

                if ($existing->num_rows > 0) {

                    $existingTeacher =
                        $existing->fetch_assoc();

                    $message =
                        "A teacher account already exists for this email. Teacher ID: "
                        . $existingTeacher['teacher_id'];

                    $message_type = "error";

                    $check->close();

                } else {

                    $check->close();


                    /* =========================================
                       GENERATE TEACHER ID
                    ========================================= */

                    $teacher_id =
                        "NISEL-T-" .
                        date("Y") .
                        "-" .
                        strtoupper(
                            substr(
                                bin2hex(
                                    random_bytes(4)
                                ),
                                0,
                                6
                            )
                        );


                    /* =========================================
                       GENERATE TEMPORARY PASSWORD
                    ========================================= */

                    $temporary_password =
                        "Nisel@" .
                        random_int(1000, 9999);


                    /*
                     * IMPORTANT:
                     * Store a HASHED password.
                     */

                    $password_hash =
                        password_hash(
                            $temporary_password,
                            PASSWORD_DEFAULT
                        );


                    /* =========================================
                       CREATE TEACHER ACCOUNT
                    ========================================= */

                    $teacher = $conn->prepare("
                        INSERT INTO teachers
                        (
                            teacher_id,
                            teacher_name,
                            email,
                            phone,
                            subjects,
                            curriculum,
                            password,
                            status
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            'Active'
                        )
                    ");


                    $teacher->bind_param(
                        "sssssss",
                        $teacher_id,
                        $teacher_name,
                        $email,
                        $phone,
                        $subjects,
                        $curriculum,
                        $password_hash
                    );


                    if ($teacher->execute()) {

                        $teacher->close();


                        /* =====================================
                           UPDATE APPLICATION
                        ===================================== */

                        $update = $conn->prepare("
                            UPDATE teacher_applications
                            SET application_status = 'Approved'
                            WHERE id = ?
                        ");

                        $update->bind_param(
                            "i",
                            $application_id
                        );

                        $update->execute();

                        $update->close();


                        /* =====================================
                           SUCCESS MESSAGE
                        ===================================== */

                        $message =
                            "Teacher application approved successfully. "
                            . "Teacher ID: "
                            . $teacher_id
                            . " | Temporary Password: "
                            . $temporary_password;

                        $message_type =
                            "success";


                    } else {

                        $message =
                            "Unable to create the teacher account. "
                            . $teacher->error;

                        $message_type =
                            "error";

                        $teacher->close();

                    }

                }

            }

        }

    }


    /* =====================================================
       REJECT APPLICATION
    ===================================================== */

    elseif ($action === "reject") {

        $stmt = $conn->prepare("
            UPDATE teacher_applications
            SET application_status = 'Rejected'
            WHERE id = ?
        ");

        $stmt->bind_param(
            "i",
            $application_id
        );

        if ($stmt->execute()) {

            $message =
                "Teacher application rejected.";

            $message_type =
                "error";

        } else {

            $message =
                "Unable to reject the application.";

            $message_type =
                "error";

        }

        $stmt->close();

    }


    /* =====================================================
       RETURN TO PENDING
    ===================================================== */

    elseif ($action === "pending") {

        $stmt = $conn->prepare("
            UPDATE teacher_applications
            SET application_status = 'Pending'
            WHERE id = ?
        ");

        $stmt->bind_param(
            "i",
            $application_id
        );

        if ($stmt->execute()) {

            $message =
                "Application returned to Pending.";

            $message_type =
                "success";

        } else {

            $message =
                "Unable to update the application.";

            $message_type =
                "error";

        }

        $stmt->close();

    }

}
