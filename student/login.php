<?php
session_start();
require "../config/db.php";

$message = "";
$message_type = "";

/* =========================================================
   STUDENT LOGIN
========================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === "" || $password === "") {
        $message = "Please enter your email and password.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM students WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $student = $result->fetch_assoc();

            if (password_verify($password, $student['password'])) {
                session_regenerate_id(true);
                $_SESSION['student_id']    = $student['id'];
                $_SESSION['student_name']  = $student['name'];
                $_SESSION['student_email'] = $student['email'];

                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Invalid email or password.";
                $message_type = "error";
            }
        } else {
            $message = "Invalid email or password.";
            $message_type = "error";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Login | NISEL Online Education</title>
<meta name="description" content="Sign in to the NISEL Online Education student portal to access your courses, assignments, and results.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --brand:#0f6d5f;
    --brand-glow:#22b39a;
    --bg:#f4f7f6;
    --card:#ffffff;
    --surface:#f8fafa;
    --fg:#10201d;
    --muted:#5f7a74;
    --border:#dfe8e5;
    --danger:#c0392b;
    --success:#1e8e5a;
    --radius:18px;
  }
  @media (prefers-color-scheme: dark){
    :root{
      --bg:#07100e; --card:#0e1a18; --surface:#111f1c; --fg:#e8f2ef;
      --muted:#8fa8a2; --border:#1e2f2b; --danger:#ff6b5c; --success:#4ade80;
    }
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{
    font-family:'Manrope',system-ui,-apple-system,Segoe UI,sans-serif;
    background:var(--bg); color:var(--fg);
    min-height:100vh; display:flex; align-items:center; justify-content:center;
    padding:40px 16px; position:relative; overflow-x:hidden;
  }
  body::before,body::after{
    content:""; position:fixed; border-radius:50%; filter:blur(90px); z-index:0; pointer-events:none;
  }
  body::before{width:340px;height:340px;top:-120px;left:-100px;background:rgba(34,179,154,.28)}
  body::after{width:380px;height:380px;bottom:-150px;right:-110px;background:rgba(15,109,95,.22)}

  .card{
    position:relative; z-index:1; width:100%; max-width:440px;
    background:color-mix(in srgb, var(--card) 88%, transparent);
    backdrop-filter:blur(18px);
    border:1px solid var(--border); border-radius:26px;
    padding:40px 34px;
    box-shadow:0 24px 60px -22px rgba(15,109,95,.38);
    animation:fadeIn .5s ease-out;
  }
  @keyframes fadeIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}

  .brand{text-align:center;margin-bottom:30px}
  .logo{
    width:64px;height:64px;margin:0 auto 18px;border-radius:20px;
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,var(--brand),var(--brand-glow));
    box-shadow:0 12px 26px -10px rgba(15,109,95,.75);
    color:#fff;font-family:'Sora',sans-serif;font-weight:700;font-size:24px;letter-spacing:.5px;
  }
  .brand h1{font-family:'Sora',sans-serif;font-size:19px;letter-spacing:.4px;color:var(--brand)}
  .brand p{margin-top:8px;font-size:14px;color:var(--muted)}

  .alert{
    display:flex;gap:10px;align-items:flex-start;
    padding:13px 14px;border-radius:14px;font-size:14px;font-weight:600;
    margin-bottom:22px;border:1px solid transparent;
  }
  .alert svg{flex:0 0 auto;margin-top:1px}
  .alert.error{color:var(--danger);background:rgba(192,57,43,.1);border-color:rgba(192,57,43,.28)}
  .alert.success{color:var(--success);background:rgba(30,142,90,.1);border-color:rgba(30,142,90,.28)}

  .form-group{margin-bottom:20px}
  .form-group label{display:block;font-size:14px;font-weight:700;margin-bottom:8px}
  .label-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  .label-row label{margin:0}
  .label-row a{font-size:12px;font-weight:600;color:var(--brand);text-decoration:none}
  .label-row a:hover{text-decoration:underline}

  .field{position:relative;display:flex;align-items:center}
  .field .icon{position:absolute;left:14px;color:var(--muted);display:flex}
  .field input{
    width:100%;padding:14px 44px;border-radius:14px;
    border:1px solid var(--border);background:var(--surface);color:var(--fg);
    font-size:15px;font-family:inherit;outline:none;transition:.2s;
  }
  .field input::placeholder{color:color-mix(in srgb,var(--muted) 70%,transparent)}
  .field input:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(34,179,154,.25)}
  .toggle{
    position:absolute;right:10px;background:none;border:0;cursor:pointer;
    color:var(--muted);padding:6px;display:flex;border-radius:8px;
  }
  .toggle:hover{color:var(--fg)}

  button[type=submit]{
    width:100%;padding:15px;border:0;border-radius:14px;cursor:pointer;
    font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#fff;
    background:linear-gradient(135deg,var(--brand),var(--brand-glow));
    box-shadow:0 14px 28px -12px rgba(15,109,95,.8);
    transition:transform .15s ease, filter .15s ease;
  }
  button[type=submit]:hover{transform:translateY(-1px);filter:brightness(1.06)}
  button[type=submit]:active{transform:translateY(0)}

  .register,.home{text-align:center;font-size:14px;color:var(--muted)}
  .register{margin-top:24px;padding-top:22px;border-top:1px solid var(--border)}
  .register a{color:var(--brand);font-weight:700;text-decoration:none}
  .register a:hover{text-decoration:underline}
  .home{margin-top:16px;font-size:13px}
  .home a{color:var(--muted);text-decoration:none}
  .home a:hover{color:var(--brand)}
</style>
</head>
<body>
<div class="card">

  <div class="brand">
    <div class="logo">N</div>
    <h1>NISEL ONLINE EDUCATION</h1>
    <p>Sign in to your student portal</p>
  </div>

  <?php if ($message !== ""): ?>
    <div class="alert <?php echo htmlspecialchars($message_type); ?>" role="alert">
      <?php if ($message_type === "success"): ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
      <?php else: ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      <?php endif; ?>
      <span><?php echo htmlspecialchars($message); ?></span>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label for="email">Email Address</label>
      <div class="field">
        <span class="icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        </span>
        <input id="email" type="email" name="email" autocomplete="email" placeholder="you@nisel.edu"
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
      </div>
    </div>

    <div class="form-group">
      <div class="label-row">
        <label for="password">Password</label>
        <a href="forgot-password.php">Forgot password?</a>
      </div>
      <div class="field">
        <span class="icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </span>
        <input id="password" type="password" name="password" autocomplete="current-password" placeholder="••••••••" required>
        <button type="button" class="toggle" id="togglePw" aria-label="Show password">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>

    <button type="submit">Login to Student Portal</button>
  </form>

  <div class="register">
    Don't have a student account?
    <a href="register.php">Register</a>
  </div>

  <div class="home">
    <a href="../index.html">← Return to NISEL ONLINE EDUCATION</a>
  </div>
</div>

<script>
  const pw = document.getElementById('password');
  document.getElementById('togglePw').addEventListener('click', function () {
    const shown = pw.type === 'text';
    pw.type = shown ? 'password' : 'text';
    this.setAttribute('aria-label', shown ? 'Show password' : 'Hide password');
  });
</script>
</body>
</html>
