<?php
/**
 * Billard Stream — Login
 */
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (isset($klubber[$username]) && password_verify($password, $klubber[$username])) {
        $_SESSION['klub_id'] = $username;
        $_SESSION['klub_navn'] = strtoupper($username);
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Ugyldigt brugernavn eller adgangskode';
}
?><!DOCTYPE html>
<html lang="da">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billard Stream — Login</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Nunito Sans',Arial,sans-serif; background:#0a0a0a; color:#e0e0e0; min-height:100vh; display:flex; justify-content:center; align-items:center; }
.login-box { background:#111; border:1px solid #1a3a2a; border-radius:16px; padding:3rem; max-width:400px; width:100%; text-align:center; }
h1 { font-size:1.5rem; color:#00ff41; margin-bottom:.3rem; letter-spacing:.1em; }
.sub { color:#666; font-size:.8rem; margin-bottom:2rem; }
.form-group { margin-bottom:1rem; text-align:left; }
label { display:block; font-size:.8rem; color:#888; margin-bottom:.3rem; text-transform:uppercase; letter-spacing:.05em; }
input { width:100%; padding:.8rem 1rem; background:#1a1a1a; border:1px solid #2a2a2a; border-radius:8px; color:#e0e0e0; font-size:1rem; }
input:focus { outline:none; border-color:#00ff41; }
.btn { width:100%; padding:.8rem; background:#00ff41; color:#0a0a0a; border:none; border-radius:8px; font-size:1rem; font-weight:600; cursor:pointer; margin-top:.5rem; }
.btn:hover { background:#00cc33; }
.error { background:#2a0a0a; color:#ff4444; padding:.6rem; border-radius:8px; margin-bottom:1rem; font-size:.85rem; border:1px solid #441111; }
.footer { margin-top:2rem; font-size:.75rem; color:#444; }
</style>
</head>
<body>
<div class="login-box">
    <h1>BILLARD STREAM</h1>
    <p class="sub">Klub-adgang</p>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="username">Klubnavn</label>
            <input type="text" id="username" name="username" required placeholder="f.eks. fbk">
        </div>
        <div class="form-group">
            <label for="password">Adgangskode</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">▶ LOG IND</button>
    </form>
    <p class="footer">Wahl-IT Development &amp; Research</p>
</div>
</body>
</html>
