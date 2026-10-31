<?php
require_once __DIR__ . '/config/config.php';

$message = '';
$status = 'info';

if (isset($_POST['install_mysql'])) {
    $host = trim($_POST['db_host'] ?? DB_HOST);
    $port = trim($_POST['db_port'] ?? DB_PORT);
    $user = trim($_POST['db_user'] ?? DB_USER);
    $pass = $_POST['db_pass'] ?? DB_PASS;
    $dbname = trim($_POST['db_name'] ?? DB_NAME);

    try {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$dbname`;");

        $sqlFile = __DIR__ . '/database/speace_db.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
            $message = "SUCCESS! Database '$dbname' and all S-PEACE tables and seed data have been initialized on MySQL!";
            $status = 'success';
        } else {
            $message = "Database '$dbname' created, but database/speace_db.sql was not found.";
            $status = 'warning';
        }
    } catch (Exception $e) {
        $message = "MySQL Connection Failed: " . $e->getMessage() . ". Ensure MySQL is started in XAMPP Control Panel.";
        $status = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>S-PEACE | MySQL Database Setup &amp; Diagnostics</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/pcb-logo.svg">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background: #0f172a;
      color: #f8fafc;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .install-box {
      background: #1e293b;
      border-radius: 20px;
      max-width: 580px;
      width: 100%;
      padding: 32px 28px;
      box-shadow: 0 25px 60px -15px rgba(0,0,0,0.6);
      border: 1px solid #334155;
    }
    .status-alert {
      padding: 14px 16px;
      border-radius: 12px;
      font-size: 13px;
      line-height: 1.45;
      margin-bottom: 20px;
    }
    .status-alert.success { background: #064e3b; border: 1px solid #10b981; color: #a7f3d0; }
    .status-alert.error { background: #7f1d1d; border: 1px solid #ef4444; color: #fecaca; }
    .status-alert.warning { background: #78350f; border: 1px solid #f59e0b; color: #fef3c7; }
    .status-alert.info { background: #0c4a6e; border: 1px solid #0284c7; color: #bae6fd; }
  </style>
</head>
<body>

  <div class="install-box">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <img src="assets/images/pcb-logo.svg" alt="PCB" style="width:48px; height:48px;">
      <div>
        <h1 style="font-size:20px; font-weight:800; color:#ffffff;">S-PEACE Database Setup</h1>
        <p style="font-size:12px; color:#94a3b8;">Polytechnic College of Botolan &bull; MySQL &amp; XAMPP Integration</p>
      </div>
    </div>

    <?php if ($message): ?>
      <div class="status-alert <?php echo $status; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <p style="font-size:13px; color:#cbd5e1; line-height:1.5; margin-bottom:20px;">
      S-PEACE is configured to use <strong>MySQL</strong> (<code style="color:#38bdf8;">speace_db</code>) as its primary database. If MySQL is currently active in XAMPP, you can initialize or re-seed the tables with one click below:
    </p>

    <form method="POST">
      <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:14px;">
        <div>
          <label class="form-label" style="color:#cbd5e1;">MySQL Host</label>
          <input type="text" name="db_host" class="input-rounded" value="<?php echo DB_HOST; ?>" style="background:#0f172a; border-color:#475569; color:#ffffff;">
        </div>
        <div>
          <label class="form-label" style="color:#cbd5e1;">Port</label>
          <input type="text" name="db_port" class="input-rounded" value="<?php echo DB_PORT; ?>" style="background:#0f172a; border-color:#475569; color:#ffffff;">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
        <div>
          <label class="form-label" style="color:#cbd5e1;">Username</label>
          <input type="text" name="db_user" class="input-rounded" value="<?php echo DB_USER; ?>" style="background:#0f172a; border-color:#475569; color:#ffffff;">
        </div>
        <div>
          <label class="form-label" style="color:#cbd5e1;">Password</label>
          <input type="password" name="db_pass" class="input-rounded" placeholder="(Blank by default in XAMPP)" style="background:#0f172a; border-color:#475569; color:#ffffff;">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" style="color:#cbd5e1;">Database Name</label>
        <input type="text" name="db_name" class="input-rounded" value="<?php echo DB_NAME; ?>" style="background:#0f172a; border-color:#475569; color:#ffffff;">
      </div>

      <button type="submit" name="install_mysql" class="btn-primary-green" style="width:100%; margin-top:8px; padding:14px;">
        Initialize / Re-Seed MySQL Database
      </button>
    </form>

    <div style="margin-top:24px; padding-top:18px; border-top:1px solid #334155; display:flex; justify-content:space-between; align-items:center;">
      <a href="index.php" style="color:#38bdf8; text-decoration:none; font-size:13px; font-weight:600;">&larr; Return to Student Portal</a>
      <a href="admin/index.php" style="color:#f59e0b; text-decoration:none; font-size:13px; font-weight:600;">Go to Admin Dashboard &rarr;</a>
    </div>
  </div>

</body>
</html>
