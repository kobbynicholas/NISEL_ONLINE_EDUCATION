<?php
/*
 * NISEL ONLINE EDUCATION
 * teacher/classroom.php
 *
 * Features:
 * - Existing teacher session and bookings assignment
 * - Paid booking check before LIVE status
 * - Optional camera and microphone
 * - Screen sharing
 * - WebRTC teacher/student signaling through classroom_signals
 * - Classroom chat through classroom_messages
 * - Start / End classroom
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../teacher_auth.php";
require "../config/db.php";

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function json_response(array $data) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data);
    exit;
}

$teacher_id = trim((string)($_SESSION["teacher_id"] ?? ""));
$teacher_name = trim((string)($_SESSION["teacher_name"] ?? "Teacher"));

/*
 * IMPORTANT: teacher_id is a STRING in the NISEL system, for example:
 * NISEL-T-XXXXXXXX
 * Do NOT cast it to integer. Casting it to int changes it to 0 and
 * causes the teacher authentication flow to send the user away.
 */
if ($teacher_id === "") {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        json_response([
            "success" => false,
            "message" => "Your teacher session has expired. Please log in again.",
            "redirect" => "login.php"
        ]);
    }
    header("Location: login.php");
    exit;
}

$booking_id = (int)($_GET["id"] ?? $_GET["booking_id"] ?? 0);

if ($booking_id <= 0) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        json_response(["success" => false, "message" => "No valid booking was selected."]);
    }
    die('<!doctype html><html><head><meta charset="utf-8"><title>Invalid Classroom</title><style>body{margin:0;font-family:Arial;background:#eef4fa;min-height:100vh;display:grid;place-items:center}.box{background:#fff;padding:40px;border-radius:22px;text-align:center;box-shadow:0 20px 60px #0001}.box h2{color:#063b6d}.box a{display:inline-block;margin-top:15px;padding:12px 20px;background:#063b6d;color:#fff;border-radius:10px;text-decoration:none}</style>
<style id="nisel-v12-style">
.nisel-v12-badge{
    position:fixed;
    top:78px;
    left:18px;
    z-index:9990;
    padding:6px 10px;
    border-radius:999px;
    background:rgba(11,130,198,.92);
    color:#fff;
    font:800 10px/1 Arial,sans-serif;
    letter-spacing:.5px;
    box-shadow:0 8px 22px rgba(0,0,0,.25);
}
</style>


<style id="nisel-v12-audio-style">
#remoteAudioButton.active{
    background:rgba(11,130,198,.95) !important;
    box-shadow:0 0 0 3px rgba(11,130,198,.20);
}
#remoteVideo{
    background:#020a12 !important;
}
</style>

<style id="nisel-v13-style">
.nisel-v13-badge{
    position:fixed;top:78px;left:18px;z-index:9999;
    padding:6px 10px;border-radius:999px;
    background:rgba(11,130,198,.92);color:#fff;
    font:800 10px/1 Arial,sans-serif;letter-spacing:.5px;
}
</style>
<style id="nisel-v14">
#remoteVideo{display:block !important;background:#020a12}
.nisel-v14-badge{
position:fixed;top:78px;left:18px;z-index:99999;
padding:6px 10px;border-radius:999px;
background:rgba(11,130,198,.95);color:#fff;
font:800 10px/1 Arial,sans-serif;letter-spacing:.5px
}
#remoteAudioButton.active{
background:rgba(11,130,198,.95)!important;
box-shadow:0 0 0 3px rgba(11,130,198,.2)
}
</style>

<style id="nisel-recording-v1">
.recording-indicator{
    position:absolute;
    top:18px;
    left:18px;
    z-index:1002;
    display:none;
    align-items:center;
    gap:8px;
    padding:9px 13px;
    border-radius:999px;
    background:rgba(185,28,28,.95);
    color:#fff;
    border:1px solid rgba(255,255,255,.18);
    box-shadow:0 12px 30px rgba(0,0,0,.35);
    font:800 11px/1 Arial,sans-serif;
    letter-spacing:.45px;
}
.recording-indicator.live{
    display:flex;
}
.recording-dot{
    width:9px;
    height:9px;
    border-radius:50%;
    background:#fff;
    animation:niselRecordingPulse 1s infinite;
}
@keyframes niselRecordingPulse{
    0%,100%{opacity:1;transform:scale(1)}
    50%{opacity:.35;transform:scale(.72)}
}
#recordButton.recording{
    background:#dc2626 !important;
    box-shadow:0 0 0 3px rgba(220,38,38,.22);
}
#stopRecordButton{
    background:#b42336 !important;
}
@media(max-width:700px){
    #recordButton span,
    #stopRecordButton span{
        display:none;
    }
}
</style>

</head><body><div class="nisel-v14-badge">NISEL CLASSROOM v14 + RECORDING</div><div class="nisel-v13-badge">NISEL CLASSROOM v13</div><div class="nisel-v12-badge">NISEL CLASSROOM v12</div><div class="box"><h2>Invalid Classroom</h2><p>No valid booking was selected.</p><a href="schedule.php">Return to Schedule</a></div>

<div id="niseLDiagnostic" style="
position:fixed;right:18px;bottom:78px;z-index:99999;
width:310px;max-width:calc(100vw - 36px);
background:rgba(5,18,31,.97);color:#fff;
border:1px solid rgba(255,255,255,.16);
border-radius:14px;padding:14px;
font:12px/1.45 Arial,sans-serif;
box-shadow:0 20px 50px rgba(0,0,0,.45);
display:none;">
    <div style="font-weight:800;font-size:13px;margin-bottom:8px">
        NISEL WebRTC Diagnostic
    </div>
    <div id="niseLDiagBody">Waiting...</div>
</div>


<audio
    id="remoteAudio"
    autoplay
    playsinline
    style="display:none"
></audio>

</body></html>');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND teacher_id = ? LIMIT 1");
    $stmt->execute([$booking_id, $teacher_id]);
    $booking = $stmt->fetch();
} catch (PDOException $e) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        json_response(["success" => false, "message" => "Unable to load the booking."]);
    }
    die("Unable to load the classroom.");
}

if (!$booking) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        json_response(["success" => false, "message" => "This booking is not assigned to your teacher account."]);
    }
    http_response_code(403);
    die('<!doctype html><html><head><meta charset="utf-8"><title>Access Denied</title><style>body{margin:0;font-family:Arial;background:#eef4fa;min-height:100vh;display:grid;place-items:center}.box{background:#fff;padding:40px;border-radius:22px;text-align:center;box-shadow:0 20px 60px #0001}.box h2{color:#063b6d}.box a{display:inline-block;margin-top:15px;padding:12px 20px;background:#063b6d;color:#fff;border-radius:10px;text-decoration:none}</style></head><body><div class="box"><h2>Classroom Access Denied</h2><p>This booking is not assigned to your teacher account.</p><a href="schedule.php">Return to Schedule</a></div></body></html>');
}

$subject = $booking["subjects"] ?? $booking["subject"] ?? "Online Lesson";
$student_name = $booking["student_name"] ?? "Student";
$curriculum = $booking["curriculum"] ?? $booking["curricula"] ?? "Curriculum";
$class_year = $booking["class_year"] ?? $booking["class"] ?? "Class";
$payment_status = $booking["payment_status"] ?? "Pending";
$live_status = strtolower(trim((string)($booking["live_status"] ?? "waiting")));
$room_code = trim((string)($booking["live_room_code"] ?? ""));

if ($room_code === "") {
    $room_code = "NISEL-" . $booking_id . "-" . strtoupper(substr(hash("sha256", $booking_id . microtime(true) . random_int(1000, 9999)), 0, 8));
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET live_room_code = ? WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$room_code, $booking_id, $teacher_id]);
    } catch (Throwable $e) {
        // The classroom can continue if the existing database does not permit this optional update.
    }
}

/* ========================= API ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["classroom_action"])) {
    try {
        $action = trim((string)$_POST["classroom_action"]);

        if ($action === "start_class") {
            $stmt = $pdo->prepare("SELECT payment_status, live_status, live_room_code FROM bookings WHERE id = ? AND teacher_id = ? LIMIT 1");
            $stmt->execute([$booking_id, $teacher_id]);
            $current = $stmt->fetch();

            if (!$current) {
                json_response(["success" => false, "message" => "This classroom is no longer assigned to you."]);
            }

            $payment = strtolower(trim((string)($current["payment_status"] ?? "")));
            $is_paid = in_array($payment, ["paid", "success", "successful", "completed", "complete"], true);

            if (!$is_paid) {
                json_response(["success" => false, "message" => "The student's payment has not been confirmed."]);
            }

            /*
             * Every new live class gets a NEW room code.
             * This automatically isolates the new WebRTC signaling
             * session from old offers/answers/ICE candidates without
             * requiring DELETE permissions on classroom_signals.
             */
            $room =
                "NISEL-" .
                $booking_id .
                "-" .
                strtoupper(
                    substr(
                        hash(
                            "sha256",
                            $booking_id .
                            microtime(true) .
                            random_int(1000, 999999)
                        ),
                        0,
                        10
                    )
                );

            $stmt = $pdo->prepare("
                UPDATE bookings
                SET
                    live_status = 'live',
                    live_started_at = NOW(),
                    live_ended_at = NULL,
                    live_room_code = ?
                WHERE id = ?
                  AND teacher_id = ?
            ");

            $stmt->execute([
                $room,
                $booking_id,
                $teacher_id
            ]);

            $check = $pdo->prepare("SELECT live_status, live_room_code FROM bookings WHERE id = ? AND teacher_id = ? LIMIT 1");
            $check->execute([$booking_id, $teacher_id]);
            $verify = $check->fetch();

            if (!$verify || strtolower((string)$verify["live_status"]) !== "live") {
                json_response(["success" => false, "message" => "The classroom could not be started in the database."]);
            }

            json_response([
                "success" => true,
                "status" => "live",
                "room_code" => $verify["live_room_code"] ?? $room,
                "message" => "Live classroom started successfully."
            ]);
        }

        if ($action === "end_class") {
            $stmt = $pdo->prepare("UPDATE bookings SET live_status = 'ended', live_ended_at = NOW() WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$booking_id, $teacher_id]);
            json_response(["success" => true, "status" => "ended", "message" => "Class ended successfully."]);
        }

        if ($action === "get_status") {
            $stmt = $pdo->prepare("SELECT live_status, live_started_at, live_ended_at, live_room_code FROM bookings WHERE id = ? AND teacher_id = ? LIMIT 1");
            $stmt->execute([$booking_id, $teacher_id]);
            $status = $stmt->fetch();
            json_response([
                "success" => true,
                "status" => $status["live_status"] ?? "waiting",
                "started_at" => $status["live_started_at"] ?? null,
                "ended_at" => $status["live_ended_at"] ?? null,
                "room_code" => $status["live_room_code"] ?? $room_code
            ]);
        }

        if ($action === "send_signal") {

            $signal_type =
                trim((string)($_POST["signal_type"] ?? ""));

            $signal_data =
                (string)($_POST["signal_data"] ?? "");

            $allowed = [
                "ready",
                "offer",
                "ice-candidate",
                "hangup"
            ];

            if (!in_array(
                $signal_type,
                $allowed,
                true
            )) {
                json_response([
                    "success" => false,
                    "message" => "Invalid signal type."
                ]);
            }

            if (
                $signal_data === "" ||
                strlen($signal_data) > 1000000
            ) {
                json_response([
                    "success" => false,
                    "message" => "Invalid signal data."
                ]);
            }

            /*
             * IMPORTANT:
             * Do not use the PHP $room_code captured when the page
             * was first opened. The teacher may start a NEW room
             * after the page has already loaded.
             *
             * Always read the current room from bookings.
             */
            $roomStmt = $pdo->prepare("
                SELECT live_status, live_room_code
                FROM bookings
                WHERE id = ?
                  AND teacher_id = ?
                LIMIT 1
            ");

            $roomStmt->execute([
                $booking_id,
                $teacher_id
            ]);

            $roomRow =
                $roomStmt->fetch(PDO::FETCH_ASSOC);

            $currentRoom =
                trim((string)(
                    $roomRow["live_room_code"] ?? ""
                ));

            if (
                !$roomRow ||
                strtolower(
                    trim(
                        (string)(
                            $roomRow["live_status"] ?? ""
                        )
                    )
                ) !== "live" ||
                $currentRoom === ""
            ) {
                json_response([
                    "success" => false,
                    "message" => "The live classroom is not active."
                ]);
            }

            $stmt = $pdo->prepare("
                INSERT INTO classroom_signals
                (
                    booking_id,
                    room_code,
                    sender_role,
                    signal_type,
                    signal_data
                )
                VALUES (?, ?, 'teacher', ?, ?)
            ");

            $stmt->execute([
                $booking_id,
                $currentRoom,
                $signal_type,
                $signal_data
            ]);

            json_response([
                "success" => true,
                "id" => (int)$pdo->lastInsertId()
            ]);
        }


        if ($action === "get_signals") {

            $last_id =
                max(
                    0,
                    (int)(
                        $_POST["last_id"] ?? 0
                    )
                );

            /*
             * Always use the current live room.
             */
            $roomStmt = $pdo->prepare("
                SELECT live_status, live_room_code
                FROM bookings
                WHERE id = ?
                  AND teacher_id = ?
                LIMIT 1
            ");

            $roomStmt->execute([
                $booking_id,
                $teacher_id
            ]);

            $roomRow =
                $roomStmt->fetch(PDO::FETCH_ASSOC);

            $currentRoom =
                trim((string)(
                    $roomRow["live_room_code"] ?? ""
                ));

            if (
                !$roomRow ||
                $currentRoom === ""
            ) {
                json_response([
                    "success" => true,
                    "signals" => []
                ]);
            }

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    signal_type,
                    signal_data,
                    created_at
                FROM classroom_signals
                WHERE booking_id = ?
                  AND room_code = ?
                  AND sender_role = 'student'
                  AND id > ?
                ORDER BY id ASC
                LIMIT 100
            ");

            $stmt->execute([
                $booking_id,
                $currentRoom,
                $last_id
            ]);

            json_response([
                "success" => true,
                "signals" =>
                    $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
        }

        if ($action === "send_message") {

            $message = trim((string)($_POST["message"] ?? ""));

            if ($message === "") {
                json_response([
                    "success" => false,
                    "message" => "Message cannot be empty."
                ]);
            }

            if (function_exists("mb_strlen") && mb_strlen($message) > 2000) {
                json_response([
                    "success" => false,
                    "message" => "Message is too long."
                ]);
            }

            /*
             * Chat-only fix:
             * Verify the actual classroom_messages columns before
             * inserting. This prevents a schema mismatch from causing
             * the generic classroom database error.
             */
            $columnsStmt = $pdo->query("SHOW COLUMNS FROM classroom_messages");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $columnMap = array_flip($columns);

            $required = ["booking_id", "room_code", "message"];

            foreach ($required as $requiredColumn) {
                if (!isset($columnMap[$requiredColumn])) {
                    json_response([
                        "success" => false,
                        "message" => "The classroom_messages table is missing the '" .
                            $requiredColumn . "' column."
                    ]);
                }
            }

            $insertColumns = ["booking_id", "room_code"];
            $insertValues = [$booking_id, $room_code];
            $placeholders = ["?", "?"];

            if (isset($columnMap["sender_role"])) {
                $insertColumns[] = "sender_role";
                $insertValues[] = "teacher";
                $placeholders[] = "?";
            } elseif (isset($columnMap["sender_type"])) {
                $insertColumns[] = "sender_type";
                $insertValues[] = "teacher";
                $placeholders[] = "?";
            }

            if (isset($columnMap["sender_name"])) {
                $insertColumns[] = "sender_name";
                $insertValues[] = $teacher_name;
                $placeholders[] = "?";
            } elseif (isset($columnMap["user_name"])) {
                $insertColumns[] = "user_name";
                $insertValues[] = $teacher_name;
                $placeholders[] = "?";
            }

            $insertColumns[] = "message";
            $insertValues[] = $message;
            $placeholders[] = "?";

            $sql = "INSERT INTO classroom_messages (" .
                implode(", ", $insertColumns) .
                ") VALUES (" .
                implode(", ", $placeholders) .
                ")";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($insertValues);

            json_response([
                "success" => true,
                "id" => (int)$pdo->lastInsertId()
            ]);
        }

        if ($action === "get_messages") {

            $last_message_id =
                max(
                    0,
                    (int)($_POST["last_message_id"] ?? 0)
                );

            $columnsStmt = $pdo->query(
                "SHOW COLUMNS FROM classroom_messages"
            );

            $columns =
                $columnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);

            $columnMap = array_flip($columns);

            $senderColumn =
                isset($columnMap["sender_role"])
                    ? "sender_role"
                    : (
                        isset($columnMap["sender_type"])
                            ? "sender_type"
                            : "NULL"
                    );

            $nameColumn =
                isset($columnMap["sender_name"])
                    ? "sender_name"
                    : (
                        isset($columnMap["user_name"])
                            ? "user_name"
                            : "''"
                    );

            $createdColumn =
                isset($columnMap["created_at"])
                    ? "created_at"
                    : "NULL";

            $sql = "
                SELECT
                    id,
                    {$senderColumn} AS sender_role,
                    {$nameColumn} AS sender_name,
                    message,
                    {$createdColumn} AS created_at
                FROM classroom_messages
                WHERE booking_id = ?
                  AND room_code = ?
                  AND id > ?
                ORDER BY id ASC
                LIMIT 100
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $booking_id,
                $room_code,
                $last_message_id
            ]);

            json_response([
                "success" => true,
                "messages" => $stmt->fetchAll()
            ]);
        }

        json_response(["success" => false, "message" => "Unknown classroom action."]);
    } catch (PDOException $e) {
        error_log("NISEL classroom PDO error: " . $e->getMessage());
        json_response([
            "success" => false,
            "message" => "Classroom database error: " . $e->getMessage()
        ]);
    } catch (Throwable $e) {
        error_log("NISEL classroom error: " . $e->getMessage());
        json_response([
            "success" => false,
            "message" => "Classroom error: " . $e->getMessage()
        ]);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Live Classroom | NISEL ONLINE EDUCATION</title>
<style>
*{box-sizing:border-box} :root{--blue:#0569a9;--blue2:#0b8ddd;--navy:#031b30;--green:#16a34a;--red:#dc3545;--bg:#020811;--panel:#0d1827;--line:#ffffff14;--muted:#91a2b7}
html,body{margin:0;width:100%;height:100%;font-family:Inter,Segoe UI,Arial,sans-serif;background:var(--bg);color:#fff;overflow:hidden}
.topbar{height:68px;background:linear-gradient(135deg,#032b4f,#0874b7);display:flex;align-items:center;justify-content:space-between;padding:0 20px;border-bottom:1px solid var(--line)}
.brand{display:flex;align-items:center;gap:12px}.brand-icon{width:40px;height:40px;border-radius:12px;background:#ffffff18;display:grid;place-items:center;font-size:20px}.brand-title{font-size:14px;font-weight:900}.brand-sub{font-size:9px;letter-spacing:1px;color:#b9d9ec;margin-top:3px}
.top-actions{display:flex;gap:9px;align-items:center}.status-pill{display:flex;gap:7px;align-items:center;padding:8px 12px;border-radius:99px;background:#ffffff12;font-size:10px;font-weight:900}.dot{width:8px;height:8px;border-radius:50%;background:#94a3b8}.status-pill.live .dot{background:#22c55e;box-shadow:0 0 0 5px #22c55e1c}.back{color:#fff;text-decoration:none;background:#ffffff14;padding:9px 13px;border-radius:10px;font-size:11px;font-weight:800}
.layout{height:calc(100vh - 68px);height:calc(100dvh - 68px);min-height:0;display:grid;grid-template-columns:minmax(0,1fr) 340px}.stage{min-height:0;position:relative;min-width:0;background:#020711;overflow:hidden}.remote-wrap{position:absolute;inset:0;background:radial-gradient(circle at 50% 40%,#0a35521c,transparent 38%),#020711}.remote-placeholder{position:absolute;inset:0;display:grid;place-items:center;text-align:center;color:#8ea1b6;padding:30px}.remote-placeholder div{max-width:460px}.remote-placeholder .icon{font-size:48px;margin-bottom:12px}.remote-placeholder h2{margin:0 0 8px;color:#d9e5ef}.remote-placeholder p{margin:0;line-height:1.6;font-size:13px}
#remoteVideo{width:100%;height:100%;object-fit:contain;background:#020711;display:none}.remote-name{position:absolute;left:18px;bottom:18px;background:#000a;padding:9px 12px;border-radius:10px;font-size:11px;font-weight:800;z-index:4}
#localVideo{position:absolute;right:20px;bottom:88px;width:230px;height:145px;object-fit:cover;border:2px solid #ffffff38;border-radius:16px;background:#101827;box-shadow:0 15px 40px #0009;transform:scaleX(-1);display:none;z-index:5}.screen-active #localVideo{transform:none}
.start-overlay{position:absolute;inset:0;z-index:10;display:grid;place-items:center;background:#020711f2}.start-card{text-align:center;width:min(92%,520px);padding:35px}.start-icon{width:78px;height:78px;border-radius:24px;background:linear-gradient(135deg,#075a9e,#0b8ddd);display:grid;place-items:center;margin:0 auto 18px;font-size:35px;box-shadow:0 18px 45px #0877c933}.start-card h1{font-size:27px;margin:0 0 8px}.start-card p{color:#9aabba;font-size:13px;line-height:1.6;margin:0 auto 20px;max-width:430px}.button-row{display:flex;justify-content:center;gap:10px;flex-wrap:wrap}.btn{border:0;color:#fff;border-radius:12px;padding:13px 19px;font-size:12px;font-weight:900;cursor:pointer}.btn-start{background:linear-gradient(135deg,#16a34a,#0e8b3e)}.btn-end{background:linear-gradient(135deg,#dc3545,#b91c1c);display:none}.btn:disabled{opacity:.55;cursor:not-allowed}
.controls{position:absolute;z-index:1000;bottom:14px;left:50%;transform:translateX(-50%);display:flex !important;visibility:visible !important;opacity:1 !important;pointer-events:auto;gap:8px;padding:9px;border-radius:999px;background:rgba(10,20,34,.94);border:1px solid rgba(255,255,255,.14);box-shadow:0 18px 45px rgba(0,0,0,.55);backdrop-filter:blur(14px)}.control{width:46px;height:46px;flex:0 0 46px;border:0;border-radius:50%;background:#243249;color:#fff;cursor:pointer;font-size:17px}.control:hover{background:#30415d;transform:translateY(-1px)}.control.active{background:#0b84d8}.control.off{background:#b42336}.control.share{width:auto;border-radius:23px;padding:0 15px;font-size:11px;font-weight:900}.control.share.active{background:#16a34a}
.sidebar{background:#0a1422;border-left:1px solid var(--line);display:flex;flex-direction:column;min-width:0}.lesson{padding:19px;border-bottom:1px solid var(--line)}.lesson-head{display:flex;justify-content:space-between;gap:10px}.lesson h2{margin:0;font-size:17px}.sub{font-size:10px;color:var(--muted);margin-top:6px}.badge{padding:6px 9px;border-radius:99px;background:#243249;color:#cbd5e1;font-size:8px;font-weight:900}.badge.live{background:#16a34a24;color:#4ade80}.rows{margin-top:15px}.row{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid #ffffff09;font-size:10px}.row span:first-child{color:#718198}.row span:last-child{text-align:right;font-weight:800}.device-box{margin-top:13px;padding:11px;border-radius:12px;background:#ffffff06;border:1px solid #ffffff0a}.device-title{font-size:10px;font-weight:900;margin-bottom:7px}.device-status{display:flex;justify-content:space-between;font-size:9px;color:#9aaabd;padding:4px 0}.ok{color:#4ade80}.warn{color:#fbbf24}.chat-title{padding:14px 18px;border-bottom:1px solid var(--line);font-size:11px;font-weight:900}.messages{flex:1;min-height:0;overflow:auto;padding:14px}.empty{color:#6e8096;text-align:center;padding:30px 10px;font-size:10px}.message{margin-bottom:12px}.message.mine{text-align:right}.message-name{font-size:9px;color:#708198;margin-bottom:4px}.bubble{display:inline-block;max-width:90%;padding:9px 11px;background:#1c2b41;border-radius:11px 11px 11px 3px;font-size:10px;line-height:1.45;overflow-wrap:anywhere}.mine .bubble{background:#075a9e;border-radius:11px 11px 3px 11px}.chat{display:flex;gap:7px;padding:10px;border-top:1px solid var(--line)}.chat input{flex:1;min-width:0;border:1px solid #ffffff0b;background:#152238;color:#fff;border-radius:10px;padding:11px;outline:0}.chat input:focus{border-color:#0b84d8}.chat button{border:0;background:#0877c9;color:#fff;border-radius:10px;padding:0 14px;font-weight:900;cursor:pointer}.toast{position:fixed;top:84px;left:50%;transform:translate(-50%,-15px);opacity:0;pointer-events:none;z-index:99;background:#111c2c;border:1px solid #ffffff12;border-radius:12px;padding:12px 16px;box-shadow:0 15px 40px #0007;font-size:11px;transition:.25s}.toast.show{opacity:1;transform:translate(-50%,0)}.toast.error{background:#641e29}
@media(max-width:900px){body{overflow:auto}.layout{height:auto;min-height:calc(100vh - 68px);grid-template-columns:1fr}.stage{height:68vh;min-height:500px}.sidebar{min-height:430px;border-left:0;border-top:1px solid var(--line)}}
@media(max-width:560px){.topbar{padding:0 12px}.status-pill{display:none}.brand-title{font-size:11px}.back{font-size:10px}.controls{bottom:10px}.control{width:40px;height:40px}.control.share{padding:0 12px}.start-card h1{font-size:23px}#localVideo{width:155px;height:100px;right:12px;bottom:75px}}
</style>

<style id="niseL-modern-classroom">
.layout{
    height:calc(100vh - 68px);
    height:calc(100dvh - 68px);
    min-height:0 !important;
}
.stage{
    min-height:0 !important;
    overflow:hidden;
    isolation:isolate;
}
.remote-wrap{
    min-height:0 !important;
    height:100%;
}
#remoteVideo{
    width:100%;
    height:100%;
    min-height:0 !important;
    object-fit:cover;
}
.controls{
    z-index:1000 !important;
    bottom:18px !important;
    gap:9px !important;
    padding:8px !important;
    border:1px solid rgba(255,255,255,.13) !important;
    background:rgba(6,17,29,.94) !important;
    box-shadow:0 18px 45px rgba(0,0,0,.45) !important;
}
.control{
    min-width:48px;
    height:48px !important;
    border:1px solid rgba(255,255,255,.12) !important;
    background:rgba(255,255,255,.08) !important;
    border-radius:14px !important;
    display:flex !important;
    align-items:center;
    justify-content:center;
    gap:6px;
    font-weight:700;
    transition:transform .18s ease,background .18s ease,box-shadow .18s ease;
}
.control span{
    font-size:11px;
    font-weight:800;
}
.control:hover{
    transform:translateY(-2px);
    background:rgba(255,255,255,.16) !important;
    box-shadow:0 8px 20px rgba(0,0,0,.22);
}
.control.share{
    padding:0 14px !important;
    min-width:100px;
}
.class-timer{
    min-width:74px;
    height:36px;
    padding:0 12px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:999px;
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.12);
    color:#fff;
    font:800 12px/1 Arial,sans-serif;
    letter-spacing:.7px;
}
#localVideo{
    border:2px solid rgba(255,255,255,.9) !important;
    box-shadow:0 14px 35px rgba(0,0,0,.45);
}
.sidebar{
    min-height:0;
    overflow:hidden;
}
.messages{
    min-height:0;
}
@media(max-width:700px){
    .class-timer{display:none}
    .control{width:44px;min-width:44px;height:44px !important}
    .control span{display:none}
    .control.share{min-width:44px;padding:0 10px !important}
    .controls{bottom:10px !important}
}
</style>

</head>
<body>
<header class="topbar">
    <div class="brand"><div class="brand-icon">🎓</div><div><div class="brand-title">NISEL ONLINE EDUCATION</div><div class="brand-sub">TEACHER LIVE CLASSROOM</div></div></div>
    <div class="top-actions"><div class="class-timer" id="classTimer">00:00</div><div class="status-pill" id="statusPill"><span class="dot"></span><span id="statusText">CLASS NOT STARTED</span></div><a class="back" href="schedule.php">← Schedule</a></div>
</header>

<main class="layout">
<section class="stage" id="stage">
    <div
        id="recordingIndicator"
        class="recording-indicator"
        aria-live="polite"
    >
        <span class="recording-dot"></span>
        <span>RECORDING</span>
        <span id="recordingTimer">00:00</span>
    </div>
    <div class="remote-wrap">
        <video id="remoteVideo" autoplay playsinline></video>
        <div class="remote-placeholder" id="remotePlaceholder"><div><div class="icon">👨‍🎓</div><h2>Waiting for student</h2><p>The student will appear here when they join the live classroom.</p></div></div>
    </div>
    <video id="localVideo" autoplay muted playsinline></video><div id="localLabel" style="position:absolute;right:30px;bottom:96px;z-index:51;display:none;background:#000b;color:#fff;padding:6px 9px;border-radius:8px;font-size:10px;font-weight:800">You</div>
    <div class="remote-name">👨‍🎓 <?= e($student_name) ?></div>

    <div class="start-overlay" id="startOverlay">
        <div class="start-card">
            <div class="start-icon">🎥</div>
            <h1 id="startTitle">Ready to Start?</h1>
            <p id="startDescription">Start the live class for <strong><?= e($student_name) ?></strong>. A camera and microphone are optional. You can also share your screen.</p>
            <div class="button-row"><button class="btn btn-start" id="startButton" type="button">▶ Start Live Class</button><button class="btn btn-end" id="endButton" type="button">■ End Class</button></div>
        </div>
    </div>

    <div class="controls">
        <button class="control" id="micButton" type="button" title="Microphone" aria-label="Microphone">🎤<span>Mic</span></button>
        <button class="control" id="cameraButton" type="button" title="Camera" aria-label="Camera">📷<span>Camera</span></button>
        <button class="control share" id="shareButton" type="button" title="Share screen" aria-label="Share screen">🖥️<span>Share</span></button>
        <button
    class="control"
    id="remoteAudioButton"
    type="button"
    title="Enable student audio"
    aria-label="Enable student audio"
>🔊<span>Audio</span></button>

<button
            class="control"
            id="recordButton"
            type="button"
            title="Start recording"
            aria-label="Start recording"
        >⏺<span>Record</span></button>

        <button
            class="control"
            id="stopRecordButton"
            type="button"
            title="Stop recording"
            aria-label="Stop recording"
            style="display:none"
        >⏹<span>Stop</span></button>

        <button class="control" id="fullscreenButton" type="button" title="Fullscreen" aria-label="Fullscreen">⛶<span>Full</span></button>
    </div>
</section>

<aside class="sidebar">
    <div class="lesson">
        <div class="lesson-head"><div><h2><?= e($subject) ?></h2><div class="sub"><?= e($curriculum) ?> · <?= e($class_year) ?></div></div><div class="badge" id="badge">WAITING</div></div>
        <div class="rows">
            <div class="row"><span>Student</span><span><?= e($student_name) ?></span></div>
            <div class="row"><span>Teacher</span><span><?= e($teacher_name) ?></span></div>
            <div class="row"><span>Room</span><span id="roomCode"><?= e($room_code) ?></span></div>
            <div class="row"><span>Booking</span><span><?= e($booking["booking_reference"] ?? $booking_id) ?></span></div>
            <div class="row"><span>Payment</span><span><?= e($payment_status) ?></span></div>
        </div>
        <div class="device-box"><div class="device-title">Device status</div><div class="device-status"><span>Camera</span><span id="cameraStatus" class="warn">Checking...</span></div><div class="device-status"><span>Microphone</span><span id="micStatus" class="warn">Checking...</span></div><div class="device-status"><span>Screen share</span><span id="screenStatus" class="warn">Available after start</span></div></div>
    </div>
    <div class="chat-title">💬 Classroom Chat</div>
    <div class="messages" id="messages"><div class="empty" id="emptyChat">No messages yet.</div></div>
    <form class="chat" id="chatForm"><input id="chatInput" type="text" maxlength="2000" placeholder="Message student..." autocomplete="off"><button type="submit">Send</button></form>
</aside>
</main>
<div class="toast" id="toast"></div>


<script>

/* =========================================================
   NISEL DIAGNOSTIC - SAFE
========================================================= */

function niselDiagShow() {
    var panel = document.getElementById("niseLDiagnostic");
    if (panel) panel.style.display = "block";
}

function niselDiagUpdate(lastEvent) {

    var body =
        document.getElementById("niseLDiagBody");

    if (!body) return;

    var pc =
        (typeof peerConnection !== "undefined")
            ? peerConnection
            : null;

    var rows = [
        ["Class", (typeof started !== "undefined" && started) ? "LIVE" : "WAITING"],
        ["Student READY", window.niselStudentReady ? "YES" : "NO"],
        ["Offer sent", window.niselOfferSent ? "YES" : "NO"],
        ["Answer received", window.niselAnswerReceived ? "YES" : "NO"],
        ["Camera", (typeof cameraTrack !== "undefined" && cameraTrack) ? "YES" : "NO"],
        ["Microphone", (typeof microphoneTrack !== "undefined" && microphoneTrack) ? "YES" : "NO"],
        ["Signaling", pc ? pc.signalingState : "none"],
        ["ICE gathering", pc ? pc.iceGatheringState : "none"],
        ["ICE connection", pc ? pc.iceConnectionState : "none"],
        ["Connection", pc ? pc.connectionState : "none"],
        ["Remote video", (
            typeof remoteVideo !== "undefined" &&
            remoteVideo &&
            remoteVideo.srcObject
        ) ? "YES" : "NO"],
        ["Last event", lastEvent || "—"]
    ];

    body.innerHTML =
        rows.map(function(row) {
            return (
                '<div style="display:flex;justify-content:space-between;' +
                'gap:12px;border-bottom:1px solid #ffffff12;padding:4px 0">' +
                '<span>' + row[0] + '</span>' +
                '<strong>' + row[1] + '</strong>' +
                '</div>'
            );
        }).join("");
}

"use strict";

const niselDiag = document.getElementById("niseLDiagnostic");

function niselDiagShow() {
    if (niselDiag) niselDiag.style.display = "block";
}

function niselDiagSet(rows) {
    niselDiagShow();

    const body =
        document.getElementById("niseLDiagBody");

    if (!body) return;

    body.innerHTML = rows.map(function(row) {
        return (
            '<div style="display:flex;justify-content:space-between;' +
            'gap:12px;border-bottom:1px solid #ffffff12;padding:4px 0">' +
            '<span>' + row[0] + '</span>' +
            '<strong>' + row[1] + '</strong>' +
            '</div>'
        );
    }).join("");
}

function niselDiagUpdate(extra) {
    const pc = window.peerConnection || null;

    niselDiagSet([
        ["Class", window.started ? "LIVE" : "WAITING"],
        ["Student READY", window.niselStudentReady ? "YES" : "NO"],
        ["Offer sent", window.niselOfferSent ? "YES" : "NO"],
        ["Answer received", window.niselAnswerReceived ? "YES" : "NO"],
        ["Camera", window.cameraTrack ? "YES" : "NO"],
        ["Microphone", window.microphoneTrack ? "YES" : "NO"],
        ["Signaling", pc ? pc.signalingState : "none"],
        ["ICE gathering", pc ? pc.iceGatheringState : "none"],
        ["ICE connection", pc ? pc.iceConnectionState : "none"],
        ["Connection", pc ? pc.connectionState : "none"],
        ["Remote video", (
            window.remoteVideo &&
            window.remoteVideo.srcObject
        ) ? "YES" : "NO"],
        ["Last event", extra || "—"]
    ]);
}



const BOOKING_ID = <?= (int)$booking_id ?>;
const CLASSROOM_URL = "classroom.php?id=" + BOOKING_ID;
const STUDENT_NAME = <?= json_encode((string)$student_name) ?>;

const stage = document.getElementById("stage");
const remoteVideo = document.getElementById("remoteVideo");
const remotePlaceholder = document.getElementById("remotePlaceholder");
const localVideo = document.getElementById("localVideo");
const startOverlay = document.getElementById("startOverlay");
const startButton = document.getElementById("startButton");
const endButton = document.getElementById("endButton");
const startTitle = document.getElementById("startTitle");
const startDescription = document.getElementById("startDescription");
const micButton = document.getElementById("micButton");
const cameraButton = document.getElementById("cameraButton");
const shareButton = document.getElementById("shareButton");
const fullscreenButton = document.getElementById("fullscreenButton");
const statusPill = document.getElementById("statusPill");
const statusText = document.getElementById("statusText");
const badge = document.getElementById("badge");
const cameraStatus = document.getElementById("cameraStatus");
const micStatus = document.getElementById("micStatus");
const screenStatus = document.getElementById("screenStatus");
const messages = document.getElementById("messages");
const emptyChat = document.getElementById("emptyChat");
const chatForm = document.getElementById("chatForm");
const chatInput = document.getElementById("chatInput");
const toast = document.getElementById("toast");

let localStream = null;
let cameraTrack = null;
let microphoneTrack = null;
let screenStream = null;
let screenTrack = null;
let peerConnection = null;
let started = false;
let shuttingDown = false;
let lastSignalId = 0;
let lastMessageId = 0;
let pendingCandidates = [];
let offerInProgress = false;

window.niselStudentReady = false;
window.niselOfferSent = false;
window.niselAnswerReceived = false;

const rtcConfig = {
    iceServers: [
        { urls: "stun:stun.l.google.com:19302" },
        { urls: "stun:stun1.l.google.com:19302" }
    ]
};

function toastMessage(message, error = false) {
    toast.textContent = message;
    toast.classList.toggle("error", error);
    toast.classList.add("show");
    clearTimeout(toastMessage.timer);
    toastMessage.timer = setTimeout(() => toast.classList.remove("show"), 3600);
}

async function post(data) {
    const form = new FormData();
    Object.entries(data).forEach(([key, value]) => form.append(key, value));
    const response = await fetch(CLASSROOM_URL, { method:"POST", body:form, credentials:"same-origin", cache:"no-store" });
    const text = await response.text();
    try { return JSON.parse(text); }
    catch (e) { console.error("Non-JSON classroom response:", text); throw new Error("The classroom server returned an invalid response."); }
}

function setLiveUI(live) {
    statusPill.classList.toggle("live", live);
    statusText.textContent = live ? "LIVE NOW" : "CLASS NOT STARTED";
    badge.textContent = live ? "LIVE" : "WAITING";
    badge.classList.toggle("live", live);
}

function setDeviceStatus(el, available, yesText = "Available") {
    el.textContent = available ? yesText : "Not found";
    el.className = available ? "ok" : "warn";
}

async function detectDevices() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
        setDeviceStatus(cameraStatus, false);
        setDeviceStatus(micStatus, false);
        return;
    }
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        setDeviceStatus(cameraStatus, devices.some(d => d.kind === "videoinput"));
        setDeviceStatus(micStatus, devices.some(d => d.kind === "audioinput"));
    } catch (e) {
        cameraStatus.textContent = "Check on start";
        micStatus.textContent = "Check on start";
    }
}

async function openOptionalMedia() {
    localStream = new MediaStream();

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        cameraStatus.textContent = "Unavailable";
        micStatus.textContent = "Unavailable";
        return;
    }

    try {
        const camStream = await navigator.mediaDevices.getUserMedia({ video:true, audio:false });
        cameraTrack = camStream.getVideoTracks()[0] || null; window.cameraTrack = cameraTrack;
        if (cameraTrack) localStream.addTrack(cameraTrack);
        setDeviceStatus(cameraStatus, !!cameraTrack);
    } catch (e) {
        cameraTrack = null;
        setDeviceStatus(cameraStatus, false);
        console.warn("Camera unavailable:", e.name);
    }

    try {
        const micStream = await navigator.mediaDevices.getUserMedia({ video:false, audio:true });
        microphoneTrack = micStream.getAudioTracks()[0] || null; window.microphoneTrack = microphoneTrack;
        if (microphoneTrack) localStream.addTrack(microphoneTrack);
        setDeviceStatus(micStatus, !!microphoneTrack);
    } catch (e) {
        microphoneTrack = null;
        setDeviceStatus(micStatus, false);
        console.warn("Microphone unavailable:", e.name);
    }

    if (localStream.getVideoTracks().length) {
        localVideo.srcObject = localStream;
        localVideo.play().catch(function() {});
        localVideo.style.display = "block";
        const localLabel = document.getElementById("localLabel");
        if (localLabel) localLabel.style.display = "block";
        localVideo.play().catch(() => {});
    }
}

function createPeerConnection() {
    if (peerConnection) return peerConnection;

    peerConnection = new RTCPeerConnection(rtcConfig); window.peerConnection = peerConnection;

    /*
     * IMPORTANT:
     * Use addTrack(track, localStream) instead of creating a bare
     * transceiver and later calling replaceTrack().
     *
     * Supplying the MediaStream here gives WebRTC an explicit
     * stream/msid association. That makes the remote ontrack event
     * reliably carry event.streams[0], including on Chrome.
     */
    window.niselVideoSender = null;
    window.niselAudioSender = null;

    if (localStream) {

        if (cameraTrack) {
            window.niselVideoSender =
                peerConnection.addTrack(
                    cameraTrack,
                    localStream
                );
        }

        if (microphoneTrack) {
            window.niselAudioSender =
                peerConnection.addTrack(
                    microphoneTrack,
                    localStream
                );
        }
    }

    peerConnection.ontrack = event => {
        console.log(
            "NISEL v14: remote track received:",
            event.track.kind,
            "streams:",
            event.streams ? event.streams.length : 0
        );

        let stream =
            event.streams && event.streams.length
                ? event.streams[0]
                : null;

        if (!stream) {
            stream = remoteVideo.srcObject;
            if (!(stream instanceof MediaStream)) {
                stream = new MediaStream();
            }
            if (!stream.getTracks().some(t => t.id === event.track.id)) {
                stream.addTrack(event.track);
            }
        }

        if (event.track.kind === "video") {
            remoteVideo.srcObject = stream;
            remoteVideo.autoplay = true;
            remoteVideo.playsInline = true;
            remoteVideo.muted = true;
            remoteVideo.style.display = "block";
            remoteVideo.style.visibility = "visible";
            remoteVideo.classList.add("remote-connected");

            if (remotePlaceholder) {
                remotePlaceholder.style.display = "none";
            }

            remoteVideo.play().catch(function(err) {
                console.warn("NISEL v14 remote video play:", err);
            });

            event.track.onunmute = function() {
                remoteVideo.play().catch(function(){});
            };
        }

        if (event.track.kind === "audio") {
            const audio = document.getElementById("remoteAudio");

            if (audio) {
                /*
                 * Use a stream containing ONLY the remote audio track.
                 * This makes the Audio button independent of the video element.
                 */
                audio.srcObject = new MediaStream([event.track]);
                audio.autoplay = true;
                audio.playsInline = true;
                audio.muted = true;

                console.log("NISEL v14: student audio track received.");
            }
        }

        event.track.onended = function() {
            console.log(
                "NISEL v14: student",
                event.track.kind,
                "track ended."
            );
        };
    };;

    /*
     * Teacher can explicitly enable/disable student audio.
     * The video remains available even when muted.
     */
    const remoteAudioButton =
        document.getElementById("remoteAudioButton");

    if (remoteAudioButton) {
        remoteAudioButton.addEventListener("click", async function() {

            const audio =
                document.getElementById("remoteAudio");

            if (!audio || !audio.srcObject) {
                console.warn(
                    "NISEL v14: student audio has not arrived yet."
                );
                return;
            }

            try {
                const enable = audio.muted;

                audio.muted = !enable;

                if (enable) {
                    await audio.play();
                } else {
                    audio.pause();
                }

                remoteAudioButton.classList.toggle("active", enable);
                remoteAudioButton.innerHTML =
                    enable
                        ? "🔊<span>Audio</span>"
                        : "🔇<span>Audio</span>";

                remoteAudioButton.title =
                    enable
                        ? "Mute student audio"
                        : "Enable student audio";

            } catch (error) {
                console.error("NISEL v14 audio:", error);
                audio.muted = true;
                remoteAudioButton.classList.remove("active");
                remoteAudioButton.innerHTML = "🔇<span>Audio</span>";
            }
        });
    }
    /*
     * NISEL v8 uses non-trickle ICE.
     * We wait until ICE gathering is complete and send one complete
     * SDP offer. This avoids losing early ICE candidates while the
     * classroom is transitioning from waiting -> live.
     */
    peerConnection.onicecandidate = event => {
        console.log(
            "NISEL teacher ICE candidate:",
            event.candidate ? "gathered" : "gathering-complete"
        );
    };

    peerConnection.onconnectionstatechange = () => {
        const state = peerConnection.connectionState;

        console.log("NISEL v12 teacher WebRTC connection:", state); niselDiagUpdate("connection=" + state); niselDiagUpdate("connection=" + state);

        if (state === "connected") {
            remotePlaceholder.style.display = "none";
            remoteVideo.style.display = "block";
            toastMessage("Student connected to the live classroom.");
        }

        if (state === "connecting") {
            toastMessage("Connecting to the student...");
        }

        if (state === "disconnected") {
            toastMessage("Student connection was interrupted.", true);
        }

        if (state === "failed") {
            toastMessage(
                "Video connection failed. Please have the student leave and join again.",
                true
            );
        }
    };

    peerConnection.oniceconnectionstatechange = () => {
        console.log(
            "NISEL teacher ICE state:",
            peerConnection.iceConnectionState
        );
    };

    peerConnection.onsignalingstatechange = () => {
        console.log(
            "NISEL teacher signaling state:",
            peerConnection.signalingState
        );
    };

    return peerConnection;
}

async function sendSignal(type, data) {
    try {
        return await post({ classroom_action:"send_signal", signal_type:type, signal_data:JSON.stringify(data) });
    } catch (e) {
        console.error("Signal send error", e);
        return { success:false, message:e.message };
    }
}

function waitForIceGatheringComplete(pc, timeout = 10000) {
    return new Promise(resolve => {
        if (!pc || pc.iceGatheringState === "complete") {
            resolve();
            return;
        }

        let finished = false;

        const done = () => {
            if (finished) return;
            finished = true;
            pc.removeEventListener(
                "icegatheringstatechange",
                check
            );
            resolve();
        };

        const check = () => {
            if (pc.iceGatheringState === "complete") {
                done();
            }
        };

        pc.addEventListener(
            "icegatheringstatechange",
            check
        );

        setTimeout(done, timeout);
    });
}

async function createOffer() {
    if (!peerConnection || !started || offerInProgress) {
        return;
    }

    if (
        peerConnection.signalingState !==
        "stable"
    ) {
        console.warn(
            "NISEL teacher: signaling state is not stable:",
            peerConnection.signalingState
        );
        return;
    }

    offerInProgress = true;

    try {

        const offer =
            await peerConnection.createOffer();

        await peerConnection.setLocalDescription(
            offer
        );

        /*
         * Wait for ICE candidates to be inserted into the SDP.
         */
        await waitForIceGatheringComplete(
            peerConnection
        );

        const description =
            peerConnection.localDescription;

        const result =
            await sendSignal(
                "offer",
                description.toJSON
                    ? description.toJSON()
                    : description
            );

        if (!result.success) {
            throw new Error(
                result.message ||
                "The offer could not be sent."
            );
        }

        console.log(
            "NISEL teacher: offer sent."
        ); window.niselOfferSent = true; niselDiagUpdate("OFFER sent");

    } catch (e) {

        console.error(
            "Offer error",
            e
        );

        toastMessage(
            e.message ||
            "Unable to create the video connection.",
            true
        );

    } finally {

        offerInProgress = false;
    }
}

async function processPendingCandidates() {
    if (!peerConnection || !peerConnection.remoteDescription) return;
    while (pendingCandidates.length) {
        const candidate = pendingCandidates.shift();
        try { await peerConnection.addIceCandidate(candidate); } catch (e) { console.warn("ICE error", e); }
    }
}

async function processSignal(signal) {
    try {
        if (signal.signal_type === "ready") { window.niselStudentReady = true; niselDiagUpdate("READY received");

            console.log(
                "NISEL teacher: student READY received."
            );

            toastMessage(
                "Student detected. Negotiating video..."
            );

            await createOffer();

            /*
             * A short retry protects against the browser still
             * negotiating its local description when READY arrives.
             */
            setTimeout(
                () => createOffer(),
                300
            );

            return;
        }

        if (signal.signal_type === "answer") {
            if (!peerConnection) return;
            const answer = JSON.parse(signal.signal_data);
            await peerConnection.setRemoteDescription(
                new RTCSessionDescription(answer)
            );

            await processPendingCandidates();

            console.log(
                "NISEL teacher: student ANSWER accepted."
            ); window.niselAnswerReceived = true; niselDiagUpdate("ANSWER received");

            toastMessage(
                "Student video connection established."
            );

            return;
        }

        if (signal.signal_type === "ice-candidate") {
            const candidate = new RTCIceCandidate(JSON.parse(signal.signal_data));
            if (peerConnection && peerConnection.remoteDescription) {
                await peerConnection.addIceCandidate(candidate);
            } else {
                pendingCandidates.push(candidate);
            }
            return;
        }

        if (signal.signal_type === "hangup") {
            if (peerConnection) {
                peerConnection.close();
                peerConnection = null;
            }
            remoteVideo.srcObject = null;
            remoteVideo.style.display = "none";
            remotePlaceholder.style.display = "grid";
            toastMessage("The student left the classroom.");
        }
    } catch (e) {
        console.error("Signal processing error", e);
        throw e;
    }
}

async function pollSignals() {
    if (!started || shuttingDown) return;
    try {
        const result = await post({ classroom_action:"get_signals", last_id:lastSignalId });
        if (result.success && Array.isArray(result.signals)) {
            for (const signal of result.signals) {
                const signalId =
                    parseInt(signal.id, 10) || 0;

                await processSignal(signal);

                /*
                 * Advance only after processing succeeds.
                 * If WebRTC temporarily rejects a signal, it will
                 * be retried on the next poll instead of being lost.
                 */
                lastSignalId =
                    Math.max(
                        lastSignalId,
                        signalId
                    );
            }
        }
    } catch (e) { console.warn("Signal polling error", e); }
}

startButton.addEventListener("click", async () => {
    if (started) return;
    startButton.disabled = true;
    startButton.textContent = "Starting...";

    try {
        // Media is optional. Failure of either device MUST NOT stop the class.
        await openOptionalMedia();

        /*
         * Start the database classroom FIRST.
         * Then create the WebRTC peer connection so ICE candidates
         * can never be generated against an old/non-live room.
         */
        const result = await post({ classroom_action:"start_class" });
        if (!result.success) {
            stopLocalMedia();
            startButton.disabled = false;
            startButton.textContent = "▶ Start Live Class";
            toastMessage(result.message || "Unable to start the classroom.", true);
            return;
        }

        /*
         * Only create WebRTC after the server has successfully
         * created the new live room.
         */
        createPeerConnection();

        started = true; window.started = true;
        shuttingDown = false;

        /*
         * The server creates a fresh room for every live session.
         * Keep the browser display synchronized with that room.
         */
        const roomDisplay =
            document.getElementById("roomCode");

        if (
            roomDisplay &&
            result.room_code
        ) {
            roomDisplay.textContent =
                result.room_code;
        }

        setLiveUI(true); niselDiagShow(); niselDiagUpdate("class started");
        startOverlay.style.display = "none";
        endButton.style.display = "inline-block";
        shareButton.classList.remove("active");
        screenStatus.textContent = "Ready";
        screenStatus.className = "ok";
        toastMessage("Live classroom started. Waiting for the student...");
        pollSignals();
    } catch (e) {
        console.error("Start error", e);
        startButton.disabled = false;
        startButton.textContent = "▶ Start Live Class";
        toastMessage(e.message || "Unable to start the classroom.", true);
    }
});

endButton.addEventListener("click", async () => {
    if (!started || !confirm("End this live class?")) return;
    endButton.disabled = true;
    try { await post({ classroom_action:"end_class" }); } catch (e) { console.error(e); }
    shutdownClass();
    endButton.disabled = false;
});

function stopLocalMedia() {
    if (screenStream) {
        screenStream.getTracks().forEach(track => track.stop());
        screenStream = null;
    }
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    cameraTrack = null;
    microphoneTrack = null;
    screenTrack = null;
    localVideo.srcObject = null;
    localVideo.style.display = "none";
    const localLabel = document.getElementById("localLabel");
    if (localLabel) localLabel.style.display = "none";
    stage.classList.remove("screen-active");
}

function shutdownClass() {
    shuttingDown = true;
    started = false;
    stopLocalMedia();
    if (peerConnection) {
        try { peerConnection.close(); } catch (e) {}
        peerConnection = null;
    }
    remoteVideo.srcObject = null;
    remoteVideo.style.display = "none";
    remotePlaceholder.style.display = "grid";
    startOverlay.style.display = "grid";
    startTitle.textContent = "Class Ended";
    startDescription.textContent = "The live classroom has ended.";
    startButton.style.display = "none";
    endButton.style.display = "none";
    setLiveUI(false);
}

micButton.addEventListener("click", () => {
    if (!microphoneTrack) { toastMessage("No microphone is available on this computer.", true); return; }
    microphoneTrack.enabled = !microphoneTrack.enabled;
    micButton.classList.toggle("off", !microphoneTrack.enabled);
    micButton.textContent = microphoneTrack.enabled ? "🎤" : "🔇";
});

cameraButton.addEventListener("click", () => {
    if (!cameraTrack) { toastMessage("No camera is available on this computer.", true); return; }
    cameraTrack.enabled = !cameraTrack.enabled;
    cameraButton.classList.toggle("off", !cameraTrack.enabled);
    cameraButton.textContent = cameraTrack.enabled ? "📷" : "🚫";
});

/* ===================== SCREEN SHARING ===================== */
shareButton.addEventListener("click", async () => {
    if (!started) { toastMessage("Start the classroom first.", true); return; }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
        toastMessage("Screen sharing is not supported by this browser.", true);
        return;
    }

    if (screenTrack) {
        await stopScreenShare();
        return;
    }

    try {
        screenStream = await navigator.mediaDevices.getDisplayMedia({
            video: { frameRate: { ideal: 30, max: 60 } },
            audio: false
        });

        screenTrack = screenStream.getVideoTracks()[0] || null;
        if (!screenTrack) throw new Error("No screen track was returned.");

        const sender = peerConnection?.getSenders().find(s => s.track && s.track.kind === "video");
        if (!sender) throw new Error("The WebRTC video sender is unavailable.");

        await sender.replaceTrack(screenTrack);

        localVideo.srcObject = screenStream;
        localVideo.style.display = "block";
        stage.classList.add("screen-active");
        shareButton.classList.add("active");
        shareButton.textContent = "⏹ Stop Sharing";
        screenStatus.textContent = "Sharing";
        screenStatus.className = "ok";

        screenTrack.onended = () => stopScreenShare();
        toastMessage("Your screen is now being shared with the student.");
    } catch (e) {
        console.error("Screen share error", e);
        if (e.name === "NotAllowedError") toastMessage("Screen sharing was cancelled.", true);
        else toastMessage(e.message || "Unable to share your screen.", true);
    }
});

async function stopScreenShare() {
    if (!screenTrack) return;

    const oldTrack = screenTrack;
    screenTrack = null;
    oldTrack.onended = null;
    oldTrack.stop();

    if (screenStream) {
        screenStream.getTracks().forEach(track => { if (track.readyState !== "ended") track.stop(); });
        screenStream = null;
    }

    const sender =
        window.niselVideoSender ||
        peerConnection?.getSenders().find(
            s => s.track && s.track.kind === "video"
        );

    if (sender) {
        window.niselVideoSender = sender;
        await sender.replaceTrack(cameraTrack || null);
    }

    if (cameraTrack) {
        localVideo.srcObject = localStream;
        localVideo.style.display = "block";
        stage.classList.remove("screen-active");
        screenStatus.textContent = "Ready";
        screenStatus.className = "ok";
    } else {
        localVideo.srcObject = null;
        localVideo.style.display = "none";
        stage.classList.remove("screen-active");
        screenStatus.textContent = "Ready";
        screenStatus.className = "ok";
    }

    shareButton.classList.remove("active");
    shareButton.textContent = "🖥️ Share Screen";
    toastMessage("Screen sharing stopped.");
}

fullscreenButton.addEventListener("click", async () => {
    try {
        if (!document.fullscreenElement) {
            if (stage.requestFullscreen) {
                await stage.requestFullscreen();
            } else if (stage.webkitRequestFullscreen) {
                stage.webkitRequestFullscreen();
            } else {
                throw new Error("Fullscreen is not supported.");
            }
        } else {
            if (document.exitFullscreen) {
                await document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
        }
    } catch (e) {
        console.warn("Fullscreen:", e);
        toastMessage("Fullscreen is not available in this browser.", true);
    }
});

document.addEventListener("fullscreenchange", () => {
    const active = !!document.fullscreenElement;
    fullscreenButton.innerHTML =
        active ? "⛶<span>Exit</span>" : "⛶<span>Full</span>";
});

chatForm.addEventListener("submit", async event => {
    event.preventDefault();
    const message = chatInput.value.trim();
    if (!message) return;
    chatInput.disabled = true;
    try {
        const result = await post({ classroom_action:"send_message", message });
        if (result.success) { chatInput.value = ""; await loadMessages(); }
        else toastMessage(result.message || "Unable to send message.", true);
    } catch (e) { toastMessage(e.message || "Unable to send message.", true); }
    finally { chatInput.disabled = false; chatInput.focus(); }
});

async function loadMessages() {
    try {
        const result = await post({ classroom_action:"get_messages", last_message_id:lastMessageId });
        if (!result.success || !Array.isArray(result.messages)) return;
        result.messages.forEach(message => {
            lastMessageId = Math.max(lastMessageId, parseInt(message.id, 10) || 0);
            if (emptyChat && emptyChat.isConnected) emptyChat.remove();
            const div = document.createElement("div");
            div.className = "message" + (message.sender_role === "teacher" ? " mine" : "");
            const name = document.createElement("div"); name.className = "message-name"; name.textContent = message.sender_name || "User";
            const bubble = document.createElement("div"); bubble.className = "bubble"; bubble.textContent = message.message || "";
            div.append(name, bubble); messages.appendChild(div);
        });
        if (result.messages.length) messages.scrollTop = messages.scrollHeight;
    } catch (e) { console.warn("Chat polling error", e); }
}

async function checkStatus() {
    if (shuttingDown) return;
    try {
        const result = await post({ classroom_action:"get_status" });
        if (!result.success) return;
        const live = String(result.status || "waiting").toLowerCase() === "live";
        setLiveUI(live);
        if (!live && started) {
            toastMessage("The classroom is no longer live.", true);
            shutdownClass();
        }
    } catch (e) { console.warn("Status check error", e); }
}

detectDevices();
loadMessages();
checkStatus();
setInterval(() => { if (started) pollSignals(); }, 500);
setInterval(loadMessages, 2200);
setInterval(checkStatus, 3500);

window.addEventListener("beforeunload", () => {
    if (screenStream) screenStream.getTracks().forEach(track => track.stop());
    if (localStream) localStream.getTracks().forEach(track => track.stop());
    if (peerConnection) peerConnection.close();
});
</script>

<script>
(function(){
    const timer = document.getElementById("classTimer");
    if (!timer) return;
    let startedAt = null;

    function render(){
        if (!startedAt){
            timer.textContent = "00:00";
            return;
        }
        const seconds = Math.max(
            0,
            Math.floor((Date.now() - startedAt) / 1000)
        );
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        timer.textContent = h > 0
            ? String(h).padStart(2,"0")+":"+String(m).padStart(2,"0")+":"+String(s).padStart(2,"0")
            : String(m).padStart(2,"0")+":"+String(s).padStart(2,"0");
    }

    setInterval(function(){
        const status = document.getElementById("statusText");
        if (status && /LIVE/i.test(status.textContent)){
            if (!startedAt) startedAt = Date.now();
        } else {
            startedAt = null;
        }
        render();
    },1000);

    render();
})();
</script>


<script>
/*
 * NISEL CLASSROOM RECORDING — v1
 *
 * This module is intentionally separate from the existing WebRTC
 * signaling code. It records in the teacher's browser using:
 *   - MediaRecorder
 *   - Canvas.captureStream()
 *   - Web Audio API
 *
 * It does NOT modify classroom_signals, chat, offer/answer or ICE.
 */
(function(){

    const recordButton =
        document.getElementById("recordButton");

    const stopRecordButton =
        document.getElementById("stopRecordButton");

    const recordingIndicator =
        document.getElementById("recordingIndicator");

    const recordingTimer =
        document.getElementById("recordingTimer");

    const teacherVideo =
        document.getElementById("localVideo");

    const studentVideo =
        document.getElementById("remoteVideo");

    const endButton =
        document.getElementById("endButton");

    if (
        !recordButton ||
        !stopRecordButton ||
        !recordingIndicator ||
        !recordingTimer ||
        !teacherVideo ||
        !studentVideo
    ){
        console.warn(
            "NISEL recording: required classroom elements are missing."
        );
        return;
    }

    let mediaRecorder = null;
    let recordingChunks = [];
    let canvas = null;
    let canvasContext = null;
    let canvasStream = null;
    let audioContext = null;
    let audioDestination = null;
    let localAudioSource = null;
    let remoteAudioSource = null;
    let animationFrame = null;
    let timerInterval = null;
    let recordingStartedAt = 0;

    function formatTime(totalSeconds){
        const hours =
            Math.floor(totalSeconds / 3600);

        const minutes =
            Math.floor(
                (totalSeconds % 3600) / 60
            );

        const seconds =
            totalSeconds % 60;

        if (hours > 0){
            return (
                String(hours).padStart(2,"0") +
                ":" +
                String(minutes).padStart(2,"0") +
                ":" +
                String(seconds).padStart(2,"0")
            );
        }

        return (
            String(minutes).padStart(2,"0") +
            ":" +
            String(seconds).padStart(2,"0")
        );
    }

    function updateTimer(){
        if (!recordingStartedAt){
            return;
        }

        const seconds =
            Math.floor(
                (Date.now() - recordingStartedAt) /
                1000
            );

        recordingTimer.textContent =
            formatTime(seconds);
    }

    function drawVideoCover(
        video,
        x,
        y,
        width,
        height
    ){
        if (
            video &&
            video.readyState >= 2 &&
            video.videoWidth > 0 &&
            video.videoHeight > 0
        ){

            const videoRatio =
                video.videoWidth /
                video.videoHeight;

            const boxRatio =
                width / height;

            let drawWidth = width;
            let drawHeight = height;
            let drawX = x;
            let drawY = y;

            if (videoRatio > boxRatio){

                drawHeight =
                    width / videoRatio;

                drawY =
                    y +
                    (height - drawHeight) /
                    2;

            }else{

                drawWidth =
                    height * videoRatio;

                drawX =
                    x +
                    (width - drawWidth) /
                    2;
            }

            canvasContext.drawImage(
                video,
                drawX,
                drawY,
                drawWidth,
                drawHeight
            );

        }else{

            canvasContext.fillStyle =
                "#101923";

            canvasContext.fillRect(
                x,
                y,
                width,
                height
            );

            canvasContext.fillStyle =
                "#8da2b8";

            canvasContext.font =
                "700 22px Arial";

            canvasContext.textAlign =
                "center";

            canvasContext.fillText(
                "Camera not available",
                x + width / 2,
                y + height / 2
            );

            canvasContext.textAlign =
                "left";
        }
    }

    function drawFrame(){

        if (!canvasContext){
            return;
        }

        const width =
            canvas.width;

        const height =
            canvas.height;

        canvasContext.fillStyle =
            "#020711";

        canvasContext.fillRect(
            0,
            0,
            width,
            height
        );

        const padding = 22;

        /*
         * Main student video.
         */
        drawVideoCover(
            studentVideo,
            padding,
            padding,
            width - padding * 2,
            height - padding * 2
        );

        /*
         * Teacher picture-in-picture.
         */
        const pipWidth = 350;
        const pipHeight = 220;

        const pipX =
            width -
            pipWidth -
            40;

        const pipY =
            height -
            pipHeight -
            40;

        canvasContext.save();

        canvasContext.shadowColor =
            "rgba(0,0,0,.65)";

        canvasContext.shadowBlur =
            25;

        canvasContext.fillStyle =
            "#111827";

        canvasContext.fillRect(
            pipX,
            pipY,
            pipWidth,
            pipHeight
        );

        drawVideoCover(
            teacherVideo,
            pipX,
            pipY,
            pipWidth,
            pipHeight
        );

        canvasContext.restore();

        /*
         * Student label.
         */
        canvasContext.fillStyle =
            "rgba(0,0,0,.72)";

        canvasContext.fillRect(
            padding + 12,
            padding + 12,
            105,
            30
        );

        canvasContext.fillStyle =
            "#fff";

        canvasContext.font =
            "700 14px Arial";

        canvasContext.fillText(
            "Student",
            padding + 26,
            padding + 32
        );

        /*
         * Teacher label.
         */
        canvasContext.fillStyle =
            "rgba(0,0,0,.72)";

        canvasContext.fillRect(
            pipX + 12,
            pipY + 12,
            100,
            30
        );

        canvasContext.fillStyle =
            "#fff";

        canvasContext.fillText(
            "Teacher",
            pipX + 26,
            pipY + 32
        );

        animationFrame =
            requestAnimationFrame(
                drawFrame
            );
    }

    function getSupportedMimeType(){

        if (
            typeof MediaRecorder ===
            "undefined"
        ){
            return "";
        }

        const types = [
            "video/webm;codecs=vp9,opus",
            "video/webm;codecs=vp8,opus",
            "video/webm"
        ];

        for (
            const type of types
        ){

            if (
                MediaRecorder.isTypeSupported(
                    type
                )
            ){
                return type;
            }
        }

        return "";
    }

    async function createRecordingStream(){

        canvas =
            document.createElement(
                "canvas"
            );

        canvas.width = 1280;
        canvas.height = 720;

        canvasContext =
            canvas.getContext(
                "2d",
                { alpha:false }
            );

        canvasStream =
            canvas.captureStream(30);

        /*
         * Create a mixed audio output.
         */
        audioContext =
            new (
                window.AudioContext ||
                window.webkitAudioContext
            )();

        audioDestination =
            audioContext.createMediaStreamDestination();

        /*
         * Teacher microphone.
         */
        if (
            window.localStream instanceof
            MediaStream
        ){

            const localTracks =
                window.localStream
                    .getAudioTracks();

            if (localTracks.length){

                localAudioSource =
                    audioContext
                        .createMediaStreamSource(
                            new MediaStream(
                                localTracks
                            )
                        );

                localAudioSource.connect(
                    audioDestination
                );
            }
        }

        /*
         * Student remote audio.
         */
        if (
            studentVideo.srcObject instanceof
            MediaStream
        ){

            const remoteTracks =
                studentVideo.srcObject
                    .getAudioTracks();

            if (remoteTracks.length){

                remoteAudioSource =
                    audioContext
                        .createMediaStreamSource(
                            new MediaStream(
                                remoteTracks
                            )
                        );

                remoteAudioSource.connect(
                    audioDestination
                );
            }
        }

        if (
            audioContext.state ===
            "suspended"
        ){
            await audioContext.resume();
        }

        const recordingStream =
            new MediaStream();

        canvasStream
            .getVideoTracks()
            .forEach(
                function(track){
                    recordingStream.addTrack(
                        track
                    );
                }
            );

        audioDestination.stream
            .getAudioTracks()
            .forEach(
                function(track){
                    recordingStream.addTrack(
                        track
                    );
                }
            );

        return recordingStream;
    }

    function cleanup(){

        if (animationFrame){
            cancelAnimationFrame(
                animationFrame
            );
            animationFrame = null;
        }

        if (timerInterval){
            clearInterval(
                timerInterval
            );
            timerInterval = null;
        }

        try{
            if (localAudioSource){
                localAudioSource.disconnect();
            }
        }catch(e){}

        try{
            if (remoteAudioSource){
                remoteAudioSource.disconnect();
            }
        }catch(e){}

        try{
            if (
                audioContext &&
                audioContext.state !==
                "closed"
            ){
                audioContext.close();
            }
        }catch(e){}

        if (canvasStream){
            canvasStream
                .getTracks()
                .forEach(
                    function(track){
                        track.stop();
                    }
                );
        }

        canvas = null;
        canvasContext = null;
        canvasStream = null;
        audioContext = null;
        audioDestination = null;
        localAudioSource = null;
        remoteAudioSource = null;
        recordingStartedAt = 0;
        recordingTimer.textContent =
            "00:00";
    }

    function saveRecording(){

        if (!recordingChunks.length){
            return;
        }

        const mimeType =
            mediaRecorder &&
            mediaRecorder.mimeType
                ? mediaRecorder.mimeType
                : "video/webm";

        const blob =
            new Blob(
                recordingChunks,
                { type:mimeType }
            );

        const now =
            new Date();

        const filename =
            "NISEL_Live_Class_" +
            now.getFullYear() +
            "-" +
            String(
                now.getMonth() + 1
            ).padStart(2,"0") +
            "-" +
            String(
                now.getDate()
            ).padStart(2,"0") +
            "_" +
            String(
                now.getHours()
            ).padStart(2,"0") +
            "-" +
            String(
                now.getMinutes()
            ).padStart(2,"0") +
            "-" +
            String(
                now.getSeconds()
            ).padStart(2,"0") +
            ".webm";

        const url =
            URL.createObjectURL(
                blob
            );

        const link =
            document.createElement(
                "a"
            );

        link.href = url;
        link.download = filename;

        document.body.appendChild(
            link
        );

        link.click();
        link.remove();

        setTimeout(
            function(){
                URL.revokeObjectURL(
                    url
                );
            },
            5000
        );

        console.log(
            "NISEL recording saved:",
            filename,
            blob.size,
            "bytes"
        );
    }

    function resetUI(){

        recordButton.style.display =
            "flex";

        stopRecordButton.style.display =
            "none";

        recordButton.classList.remove(
            "recording"
        );

        recordButton.innerHTML =
            "⏺<span>Record</span>";

        recordingIndicator.classList.remove(
            "live"
        );

        recordingStartedAt = 0;

        recordingTimer.textContent =
            "00:00";
    }

    recordButton.addEventListener(
        "click",
        async function(){

            if (
                mediaRecorder &&
                mediaRecorder.state ===
                "recording"
            ){
                return;
            }

            if (
                typeof MediaRecorder ===
                "undefined"
            ){
                alert(
                    "Recording is not supported by this browser. Please use Google Chrome or Microsoft Edge."
                );
                return;
            }

            try{

                const stream =
                    await createRecordingStream();

                const mimeType =
                    getSupportedMimeType();

                mediaRecorder =
                    mimeType
                        ? new MediaRecorder(
                            stream,
                            {
                                mimeType:
                                    mimeType
                            }
                        )
                        : new MediaRecorder(
                            stream
                        );

                recordingChunks = [];

                mediaRecorder.ondataavailable =
                    function(event){

                        if (
                            event.data &&
                            event.data.size > 0
                        ){
                            recordingChunks.push(
                                event.data
                            );
                        }
                    };

                mediaRecorder.onerror =
                    function(event){

                        console.error(
                            "NISEL recording error:",
                            event.error
                        );

                        alert(
                            "An error occurred while recording the class."
                        );
                    };

                mediaRecorder.onstop =
                    function(){

                        cleanup();

                        saveRecording();

                        resetUI();

                        toastMessage(
                            "Class recording saved to your computer."
                        );
                    };

                mediaRecorder.start(
                    1000
                );

                recordingStartedAt =
                    Date.now();

                timerInterval =
                    setInterval(
                        updateTimer,
                        1000
                    );

                recordButton.style.display =
                    "none";

                stopRecordButton.style.display =
                    "flex";

                recordingIndicator.classList.add(
                    "live"
                );

                drawFrame();

                toastMessage(
                    "Class recording started."
                );

            }catch(error){

                console.error(
                    "NISEL recording start error:",
                    error
                );

                cleanup();

                alert(
                    "Unable to start the recording. Make sure the classroom cameras and microphone are available."
                );
            }
        }
    );

    stopRecordButton.addEventListener(
        "click",
        function(){

            if (
                mediaRecorder &&
                mediaRecorder.state !==
                "inactive"
            ){
                mediaRecorder.stop();
            }
        }
    );

    /*
     * If the teacher clicks End Class while recording,
     * stop the recorder first. The normal v14 end-class
     * logic remains untouched.
     */
    if (endButton){

        endButton.addEventListener(
            "click",
            function(){

                if (
                    mediaRecorder &&
                    mediaRecorder.state !==
                    "inactive"
                ){
                    try{
                        mediaRecorder.stop();
                    }catch(e){
                        console.warn(
                            "NISEL recording stop:",
                            e
                        );
                    }
                }
            },
            true
        );
    }

})();
</script>

</body>
</html>
