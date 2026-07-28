<?php
/**
 * Billard Stream — Dashboard
 */
require_once __DIR__ . '/config.php';
requireLogin();

$klub = strtolower($_SESSION['klub_id']);
$klubNavn = $_SESSION['klub_navn'];

// Håndter start/stop
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bord'], $_POST['cmd'])) {
    $bord = (int)$_POST['bord'];
    $cmd = $_POST['cmd'];
    $commands = readData('commands_' . $klub);
    $commands[] = [
        'klub' => $klub,
        'bord' => $bord,
        'cmd' => $cmd,
        'rtsp' => "rtsp://kamera{$bord}.klubben.dk/stream",
        'rtmp' => "rtmp://a.rtmp.youtube.com/live2/XXXX-{$bord}",
        'title' => "{$klubNavn} Bord {$bord}",
        'status' => 'pending',
        'created' => date('c')
    ];
    writeData('commands_' . $klub, $commands);
    $msg = "Kommando sendt: {$cmd} bord {$bord}";
}

// Hent status for alle borde
$statuses = [];
$allStatus = readData('status');
foreach ($allStatus as $s) {
    if ($s['klub'] === $klub) {
        $statuses[$s['bord']] = $s['status'];
    }
}
?><!DOCTYPE html>
<html lang="da">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billard Stream — <?= htmlspecialchars($klubNavn) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Nunito Sans',Arial,sans-serif; background:#0a0a0a; color:#e0e0e0; min-height:100vh; }
.nav { background:#111; border-bottom:1px solid #1a3a2a; padding:.8rem 2rem; display:flex; justify-content:space-between; align-items:center; }
.nav-brand { color:#00ff41; font-size:1.1rem; font-weight:700; letter-spacing:.1em; }
.nav-user { color:#888; font-size:.85rem; }
.nav-user a { color:#ff4444; text-decoration:none; margin-left:1rem; font-size:.8rem; }
main { max-width:1000px; margin:0 auto; padding:2rem; }
h1 { font-size:1.3rem; color:#00ff41; margin-bottom:1.5rem; font-weight:400; letter-spacing:.05em; }
.msg { background:#0a1a0a; border:1px solid #004411; color:#00ff41; padding:.6rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:.85rem; }
.grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:1rem; }
.card { background:#111; border:1px solid #1a3a2a; border-radius:12px; padding:1.5rem; }
.card h3 { color:#e0e0e0; font-size:1rem; margin-bottom:.3rem; }
.card .cam { color:#666; font-size:.8rem; margin-bottom:.8rem; }
.badge { display:inline-block; padding:.25rem .6rem; border-radius:20px; font-size:.75rem; margin-bottom:.8rem; }
.badge-off { background:#1a0a0a; color:#ff4444; border:1px solid #441111; }
.badge-on { background:#0a1a0a; color:#00ff41; border:1px solid #004411; }
.badge-error { background:#1a0a0a; color:#ff8800; border:1px solid #442200; }
.card form { display:flex; gap:.5rem; }
.btn-start { flex:1; background:#00ff41; color:#0a0a0a; border:none; padding:.5rem; border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; }
.btn-start:hover { background:#00cc33; }
.btn-stop { flex:1; background:#441111; color:#ff4444; border:1px solid #661111; padding:.5rem; border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; }
.btn-stop:hover { background:#661111; }
</style>
</head>
<body>
<div class="nav">
    <span class="nav-brand">🎱 BILLARD STREAM</span>
    <div class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="cameras.php">Kameraer</a>
        <a href="stream-keys.php">Stream Keys</a>
        <a href="titles.php">Titler</a>
        <a href="schedule.php">Planlægning</a>
    </div>
    <span class="nav-user"><?= htmlspecialchars($klubNavn) ?> <a href="logout.php">[log ud]</a></span>
</div>
<main>
    <h1>🟢 Streams — <?= htmlspecialchars($klubNavn) ?></h1>
    
    <?php if (isset($msg)): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <div class="grid">
        <?php for ($bord = 1; $bord <= 4; $bord++): 
            $s = $statuses[$bord] ?? 'stopped';
            $badge = $s === 'running' ? 'badge-on' : ($s === 'error' ? 'badge-error' : 'badge-off');
            $label = $s === 'running' ? '● live' : ($s === 'error' ? '● fejl' : '● slukket');
        ?>
        <div class="card">
            <h3>Bord <?= $bord ?></h3>
            <p class="cam">IP Camera <?= $bord ?> · 1080p</p>
            <div class="badge <?= $badge ?>"><?= $label ?></div>
            <form method="post">
                <input type="hidden" name="bord" value="<?= $bord ?>">
                <?php if ($s === 'running'): ?>
                    <button type="submit" name="cmd" value="stop" class="btn-stop">⏹ STOP</button>
                <?php else: ?>
                    <button type="submit" name="cmd" value="start" class="btn-start">▶ START STREAM</button>
                <?php endif; ?>
            </form>
        </div>
        <?php endfor; ?>
    </div>
</main>
</body>
</html>
