<?php
/**
 * Billard Stream — Samlet Dashboard
 */
require_once __DIR__ . '/config.php';
requireLogin();

$klub = strtolower($_SESSION['klub_id'] ?? '');
$navn = $_SESSION['klub_navn'] ?? $klub;

// Hent data
$cameras  = readData('cameras_' . $klub) ?: [];
$keys     = readData('keys_' . $klub) ?: [];
$titles   = readData('titles_' . $klub) ?: [];
$statuses = readData('status_' . $klub) ?: [];

// Håndter start/stop
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bord'], $_POST['cmd'])) {
    $bord = (int)$_POST['bord'];
    $cmd = $_POST['cmd'];
    $cmds = readData('commands_' . $klub) ?: [];
    $cmds[] = ['klub' => $klub, 'bord' => $bord, 'cmd' => $cmd, 'status' => 'pending', 'created' => date('c')];
    writeData('commands_' . $klub, $cmds);
    $_SESSION['flash'] = "Kommando sendt: {$cmd} bord {$bord}";
    header("Location: dashboard.php");
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Hjælpefunktioner
function keyShort($key) {
    return is_string($key) ? substr($key, 0, 6) . '…' . substr($key, -4) : '—';
}
function kameraType($type) {
    return ['ip'=>'📹 IP','usb'=>'🔌 USB','builtin'=>'💻 Indbygget'][$type] ?? '📹 IP';
}
function statusBadge($s) {
    if ($s === 'running') return '<span style="color:#00ff41">● LIVE</span>';
    if ($s === 'error')   return '<span style="color:#ff4444">● FEJL</span>';
    return '<span style="color:#888">● slukket</span>';
}
function bordStatus($statuses, $bord) {
    foreach ($statuses as $s) {
        if (($s['bord'] ?? 0) == $bord) return $s['status'] ?? 'stopped';
    }
    return 'stopped';
}
function streamKey($keys, $bord) {
    foreach ($keys as $k) {
        if (($k['bord'] ?? 0) == $bord) return $k['key'] ?? '';
    }
    return '';
}
function streamTitle($titles, $bord, $default) {
    foreach ($titles as $t) {
        if (($t['bord'] ?? 0) == $bord) return $t['midlertidig'] ?? $t['default'] ?? $default;
    }
    return $default;
}
?><!DOCTYPE html>
<html lang="da">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billard Stream — <?= htmlspecialchars($navn) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Nunito Sans',Arial,sans-serif; background:#0a0a0a; color:#e0e0e0; min-height:100vh; }
.nav { background:#111; border-bottom:1px solid #1a3a2a; padding:.8rem 2rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; }
.nav-brand { color:#00ff41; font-size:1.1rem; font-weight:700; letter-spacing:.1em; }
.nav-links { display:flex; gap:.5rem; align-items:center; }
.nav-links a { color:#888; text-decoration:none; font-size:.85rem; padding:.3rem .6rem; border-radius:6px; }
.nav-links a:hover { color:#00ff41; background:#1a1a1a; }
.nav-links a.active { color:#00ff41; border:1px solid #1a3a2a; }
.dropdown { position:relative; display:inline-block; }
.dropdown-content { display:none; position:absolute; top:100%; right:0; background:#111; border:1px solid #1a3a2a; border-radius:8px; min-width:160px; z-index:10; }
.dropdown-content a { display:block; padding:.5rem 1rem; color:#888; font-size:.82rem; border-bottom:1px solid #1a1a1a; }
.dropdown-content a:last-child { border:none; }
.dropdown:hover .dropdown-content { display:block; }
.nav-user { color:#888; font-size:.85rem; }
.nav-user a { color:#ff4444; text-decoration:none; margin-left:1rem; font-size:.8rem; }
main { max-width:1000px; margin:0 auto; padding:2rem; }
.guide { background:#0a1a0a; border:1px solid #004411; border-radius:12px; padding:1rem 1.2rem; margin-bottom:1.5rem; font-size:.85rem; color:#ccc; }
.guide strong { color:#00ff41; }
.flash { background:#0a1a0a; border:1px solid #004411; color:#00ff41; padding:.6rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:.85rem; }
.empty { text-align:center; padding:3rem; color:#888; }
.empty .icon { font-size:3rem; margin-bottom:.5rem; }
.empty a { color:#00ff41; }
.grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1rem; }
.card { background:#111; border:1px solid #1a3a2a; border-radius:12px; padding:1rem; }
.card h3 { font-size:.95rem; color:#e0e0e0; margin-bottom:.6rem; }
.card .row { font-size:.8rem; color:#888; margin-bottom:.3rem; display:flex; justify-content:space-between; }
.card .row .val { color:#ccc; }
.card .status { margin:.6rem 0; }
.btn { width:100%; padding:.7rem; border:none; border-radius:8px; font-weight:600; font-size:.85rem; cursor:pointer; margin-top:.5rem; }
.btn-start { background:#00ff41; color:#0a0a0a; }
.btn-start:hover { background:#00cc33; }
.btn-stop { background:#441111; color:#ff4444; border:1px solid #661111; }
.btn-stop:hover { background:#661111; }
h1 { font-size:1.2rem; color:#00ff41; margin-bottom:1rem; font-weight:400; }
</style>
</head>
<body>
<div class="nav">
    <span class="nav-brand">🎱 BILLARD STREAM</span>
    <div class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <div class="dropdown">
            <a href="#" style="cursor:default">Indstillinger ▾</a>
            <div class="dropdown-content">
                <a href="cameras.php">📹 Kameraer</a>
                <a href="stream-keys.php">🔑 Stream Keys</a>
                <a href="titles.php">🔖 Titler</a>
                <a href="schedule.php">📅 Planlægning</a>
            </div>
        </div>
    </div>
    <span class="nav-user"><?= htmlspecialchars($navn) ?> <a href="logout.php">[log ud]</a></span>
</div>
<main>
    <div class="guide">
        <strong>🎯 Sådan streamer du:</strong><br>
        1. 📹 <a href="cameras.php" style="color:#00ff41">Tilføj kamera</a> → 
        2. 🔑 <a href="stream-keys.php" style="color:#00ff41">Indtast stream key</a> → 
        3. ⏯ Tryk START
    </div>
    
    <?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
    
    <?php if (empty($cameras)): ?>
        <div class="empty">
            <div class="icon">📹</div>
            <p>Ingen kameraer endnu</p>
            <p style="font-size:.85rem;margin-top:.5rem"><a href="cameras.php">Tilføj dit første kamera</a></p>
        </div>
    <?php else: ?>
        <div class="grid">
        <?php foreach ($cameras as $cam): 
            $b = (int)($cam['bord'] ?? 0);
            $type = $cam['type'] ?? 'ip';
            $opl = $cam['opløsning'] ?? $cam['resolution'] ?? '1080p';
            $s = bordStatus($statuses, $b);
            $key = streamKey($keys, $b);
            $title = streamTitle($titles, $b, $navn . ' Bord ' . $b);
        ?>
            <div class="card">
                <h3>Bord <?= $b ?>: <?= htmlspecialchars($cam['navn'] ?? '') ?></h3>
                <div class="row"><span><?= kameraType($type) ?> · <?= $opl ?></span></div>
                <div class="row"><span>🔑 Stream key</span><span class="val"><?= $key ? htmlspecialchars(keyShort($key)) : '—' ?></span></div>
                <div class="row"><span>📝 Titel</span><span class="val"><?= htmlspecialchars($title) ?></span></div>
                <div class="status"><?= statusBadge($s) ?></div>
                <?php if ($s === 'running'): ?>
                    <form method="post">
                        <input type="hidden" name="bord" value="<?= $b ?>">
                        <input type="hidden" name="cmd" value="stop">
                        <button type="submit" class="btn btn-stop">⏹ STOP STREAM</button>
                    </form>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="bord" value="<?= $b ?>">
                        <input type="hidden" name="cmd" value="start">
                        <button type="submit" class="btn btn-start">▶ START STREAM</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
