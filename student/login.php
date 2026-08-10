<?php

session_start();

require "../config/db.php";

$message = "";
$message_type = "";


/* =========================================================
   STUDENT LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    if ($email === "" || $password === "") {

        $message = "Please enter your email and password.";
        $message_type = "error";

    } else {

        try {

            /* Find student */

            $stmt = $pdo->prepare("
                SELECT *
                FROM students
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([
                $email
            ]);

            $student = $stmt->fetch();


            /* Check account */

            if (!$student) {

                $message = "Invalid email or password.";
                $message_type = "error";

            } elseif (
                !password_verify(
                    $password,
                    $student['password']
                )
            ) {

                $message = "Invalid email or password.";
                $message_type = "error";

            } else {

                /* =================================================
                   LOGIN SUCCESS
                ================================================= */

                session_regenerate_id(true);


                $_SESSION['student_logged_in'] = true;

                $_SESSION['student_id'] =
                    $student['id'];

                $_SESSION['student_name'] =
                    $student['student_name'];

                $_SESSION['student_email'] =
                    $student['email'];


                header(
                    "Location: dashboard.php"
                );

                exit;

            }

        } catch (PDOException $e) {

            $message =
                "Login error: "
                . $e->getMessage();

            $message_type = "error";
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Login | NISEL Online Education</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --brand:        #3b82f6;   /* blue       */
      --brand-light:  #93c5fd;   /* light blue */
      --brand-glow:   #60a5fa;   /* mid blue   */
      --white:        #ffffff;
      --bg:           #f0f7ff;   /* very light blue tint */
      --surface:      #ffffff;
      --text:         #1e3a8a;   /* deep blue text */
      --muted:        #64748b;
      --border:       #dbeafe;
      --danger:       #dc2626;
      --danger-bg:    #fef2f2;
      --success:      #047857;
      --success-bg:   #ecfdf5;
    }

    body {
      font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      position: relative;
      overflow: hidden;
    }

    /* Ambient background glows */
    body::before {
      content: "";
      position: absolute;
      top: -6rem;
      left: -6rem;
      width: 18rem;
      height: 18rem;
      background: radial-gradient(circle, var(--brand-light), transparent 70%);
      opacity: .35;
      filter: blur(60px);
      pointer-events: none;
    }

    body::after {
      content: "";
      position: absolute;
      bottom: -8rem;
      right: -5rem;
      width: 22rem;
      height: 22rem;
      background: radial-gradient(circle, var(--brand-glow), transparent 70%);
      opacity: .25;
      filter: blur(70px);
      pointer-events: none;
    }

    .card {
      position: relative;
      width: 100%;
      max-width: 28rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 1.5rem;
      padding: 2.5rem;
      box-shadow: 0 20px 60px -20px rgba(59, 130, 246, .35);
      backdrop-filter: blur(12px);
      animation: fadeIn .5s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Brand */
    .brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      margin-bottom: 2rem;
    }

    .brand .logo {
      width: 4rem;
      height: 4rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 1rem;
      background: linear-gradient(135deg, var(--brand), var(--brand-glow));
      box-shadow: 0 10px 20px -8px rgba(59, 130, 246, .5);
      margin-bottom: 1.25rem;
      color: var(--white);
      font-size: 1.75rem;
      font-weight: 700;
    }

    .brand h1 {
      font-size: 1.25rem;
      font-weight: 700;
      letter-spacing: -.02em;
      color: var(--brand);
    }

    .brand p {
      margin-top: .5rem;
      font-size: .875rem;
      color: var(--muted);
    }

    /* Alert message */
    .alert {
      display: none;
      align-items: flex-start;
      gap: .6rem;
      padding: .9rem 1rem;
      border-radius: .75rem;
      font-size: .875rem;
      font-weight: 500;
      margin-bottom: 1.5rem;
      border: 1px solid transparent;
    }

    .alert.show { display: flex; }

    .alert.error {
      background: var(--danger-bg);
      border-color: rgba(220, 38, 38, .3);
      color: var(--danger);
    }

    .alert.success {
      background: var(--success-bg);
      border-color: rgba(4, 120, 87, .3);
      color: var(--success);
    }

    .alert svg {
      width: 1rem;
      height: 1rem;
      margin-top: .125rem;
      flex-shrink: 0;
    }

    /* Form */
    form {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: .5rem;
    }

    .form-group label {
      font-size: .875rem;
      font-weight: 600;
      color: var(--text);
    }

    .form-group .label-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .form-group .label-row a {
      font-size: .75rem;
      font-weight: 500;
      color: var(--brand);
      text-decoration: none;
    }

    .form-group .label-row a:hover { text-decoration: underline; }

    .input-wrap {
      position: relative;
    }

    .input-wrap .icon {
      position: absolute;
      left: .9rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      pointer-events: none;
      width: 1.25rem;
      height: 1.25rem;
    }

    .input-wrap input {
      width: 100%;
      padding: .75rem .9rem .75rem 2.75rem;
      border: 1px solid var(--border);
      border-radius: .75rem;
      background: var(--bg);
      color: var(--text);
      font-size: .9375rem;
      outline: none;
      transition: all .2s ease;
    }

    .input-wrap input::placeholder { color: rgba(100, 116, 139, .6); }

    .input-wrap input:focus {
      border-color: var(--brand);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, .2);
    }

    .input-wrap.has-toggle input { padding-right: 2.75rem; }

    .toggle-pw {
      position: absolute;
      right: .6rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      padding: .25rem;
      color: var(--muted);
      cursor: pointer;
      border-radius: .375rem;
      transition: color .2s ease;
    }

    .toggle-pw:hover { color: var(--text); }

    .toggle-pw svg {
      width: 1.25rem;
      height: 1.25rem;
    }

    /* Submit button */
    .btn-submit {
      width: 100%;
      padding: .875rem;
      border: none;
      border-radius: .75rem;
      background: linear-gradient(to right, var(--brand), var(--brand-glow));
      color: var(--white);
      font-size: .9375rem;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 10px 20px -8px rgba(59, 130, 246, .45);
      transition: all .2s ease;
    }

    .btn-submit:hover {
      filter: brightness(1.05);
      box-shadow: 0 14px 28px -8px rgba(59, 130, 246, .55);
    }

    .btn-submit:active { transform: scale(.99); }

    .btn-submit:disabled {
      opacity: .7;
      cursor: not-allowed;
    }

    .btn-submit .spinner {
      width: 1rem;
      height: 1rem;
      border: 3px solid rgba(255, 255, 255, .3);
      border-top-color: var(--white);
      border-radius: 50%;
      animation: spin .6s linear infinite;
      display: inline-block;
      vertical-align: middle;
      margin-right: .5rem;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* Footer links */
    .footer {
      margin-top: 2rem;
      text-align: center;
      display: flex;
      flex-direction: column;
      gap: .75rem;
    }

    .footer p { font-size: .875rem; color: var(--muted); }

    .footer a { color: var(--brand); font-weight: 600; text-decoration: none; }
    .footer a:hover { text-decoration: underline; }

    .footer .home a {
      display: inline-flex;
      align-items: center;
      gap: .25rem;
      color: var(--muted);
      font-weight: 400;
      transition: color .2s ease;
    }

    .footer .home a:hover { color: var(--text); }

    .copyright {
      margin-top: 1.5rem;
      text-align: center;
      font-size: .75rem;
      color: var(--muted);
    }
  </style>
</head>
<body>
  <div class="card">
    <!-- Brand -->
    <div class="brand">
      <div class="logo">N</div>
      <h1>NISEL ONLINE EDUCATION</h1>
      <p>Sign in to your student portal</p>
    </div>

    <!-- Message (toggle .show / .error / .success via PHP) -->
    <div class="alert error show" role="alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10" />
        <path d="M12 8v4M12 16h.01" />
      </svg>
     // <span>Invalid email or password.</span>
    </div>

    <!-- Form -->
    <form method="POST" action="" novalidate>
      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-wrap">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="2" />
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
          </svg>
          <input type="email" id="email" name="email"
                 autocomplete="email" required placeholder="you@nisel.edu" />
        </div>
      </div>

      <div class="form-group">
        <div class="label-row">
          <label for="password">Password</label>
          <a href="#">Forgot password?</a>
        </div>
        <div class="input-wrap has-toggle">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
          </svg>
          <input type="password" id="password" name="password"
                 autocomplete="current-password" required placeholder="••••••••" />
          <button type="button" class="toggle-pw" id="togglePw"
                  aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-submit">Login to Student Portal</button>
    </form>


  <script>
    // Password show/hide
    const pw = document.getElementById('password');
    const togglePw = document.getElementById('togglePw');
    togglePw.addEventListener('click', function () {
      const shown = pw.type === 'text';
      pw.type = shown ? 'password' : 'text';
      this.setAttribute('aria-label', shown ? 'Show password' : 'Hide password');
    });

    // Footer year
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>
</body>
</html>


</head>


<body>


<div class="container">


<div class="logo">

NISEL ONLINE EDUCATION

</div>


<div class="subtitle">

Student Login

</div>


<?php if ($message !== ""): ?>

<div class="message <?php echo $message_type; ?>">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>


</div>


</body>

</html>
