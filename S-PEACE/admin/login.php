<?php
require_once __DIR__ . '/../config/db.php';

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDb();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'staff')");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'department' => $user['department'] ?? 'Facilities'
            ];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid administrative credentials. Use admin@pcb.edu.ph / admin123';
        }
    } else {
        $error = 'Database connection error: ' . $GLOBALS['db_status_message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | S-PEACE - Polytechnic College of Botolan</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/pcb-logo.svg">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      background: radial-gradient(circle at 50% 30%, #1e293b 0%, #0f172a 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .admin-card {
      background: #ffffff;
      border-radius: 20px;
      max-width: 420px;
      width: 100%;
      padding: 32px 28px;
      box-shadow: 0 25px 60px -15px rgba(0,0,0,0.5);
    }
  </style>
</head>
<body>

  <div class="admin-card">
    <div style="text-align:center; margin-bottom:24px;">
      <img src="../assets/images/pcb-logo.svg" alt="PCB Logo" style="width:56px; height:56px; margin-bottom:10px;">
      <h1 style="font-size:22px; font-weight:800; color:#111827;">S-PEACE Administration</h1>
      <p style="font-size:12px; color:#6b7280; margin-top:4px;">Facilities &amp; Maintenance Central Portal</p>
      <p style="font-size:11px; color:#15803d; font-weight:700; margin-top:2px;">Polytechnic College of Botolan</p>
    </div>

    <?php if (!empty($error)): ?>
      <div style="background:#fee2e2; border:1px solid #ef4444; color:#991b1b; padding:10px 14px; border-radius:10px; font-size:13px; margin-bottom:18px;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label" style="font-size:13px;">Admin Email</label>
        <input type="email" name="email" class="input-rounded" value="admin@pcb.edu.ph" required>
      </div>

      <div class="form-group">
        <label class="form-label" style="font-size:13px;">Password</label>
        <input type="password" name="password" class="input-rounded" value="admin123" required>
      </div>

      <button type="submit" class="btn-primary-green" style="width:100%; margin-top:8px; padding:13px;">Sign In to Dashboard</button>
    </form>

    <div style="margin-top:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px; font-size:12px; color:#475569;">
      <strong>Default Admin Credentials:</strong><br>
      Email: <code style="color:#0f766e;">admin@pcb.edu.ph</code><br>
      Password: <code style="color:#0f766e;">admin123</code>
    </div>

    <div style="text-align:center; margin-top:16px;">
      <a href="../index.php" style="font-size:13px; color:#64748b; text-decoration:none; font-weight:600;">&larr; Back to Student Portal</a>
    </div>
  </div>

</body>
</html>
