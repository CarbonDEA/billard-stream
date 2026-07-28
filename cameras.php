<?php
/**
 * Billard Stream — Kamera Administration
 */
require_once __DIR__ . '/config.php';
requireLogin();

$klub = strtolower($_SESSION['klub_id']);
$klubNavn = $_SESSION['klub_navn'];

$msg = "";

// Håndter tilføjelse af kamera
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_camera'])) {
    $bord = (int)$_POST['bord'];
    $navn = trim($_POST['navn']);
    $type = $cam['type'] ?? 'ip';
    $opl = $cam['opløsning'] ?? $cam['resolution'] ?? '1080p';
    $info = $type === 'ip' ? ($cam['rtsp'] ?? $cam['ip'] ?? '') : ($cam['device'] ?? '');
    $label = ['ip'=>'📡 IP','usb'=>'🔌 USB','builtin'=>'💻 Indbygget'][$type] ?? '📡 IP';
    
    $rtsp = "";
    $device = "";
    $ip = trim($_POST['ip'] ?? '');

    if ($type === 'ip') {
        $rtsp = trim($_POST['rtsp']);
    } elseif ($type === 'usb') {
        $device = trim($_POST['device']);
    }

    // Validering baseret på type
    $isValid = false;
    if ($type === 'ip' && $bord > 0 && $navn !== '' && $rtsp !== '') {
        $isValid = true;
    } elseif ($type === 'usb' && $bord > 0 && $navn !== '' && $device !== '') {
        $isValid = true;
    } elseif ($type === 'builtin' && $bord > 0 && $navn !== '') {
        $isValid = true;
    }

    if ($isValid) {
        // Brug klub-specifik datafil som anmodet: readData('cameras_' . $klub)
        $cameras = readData('cameras_' . $klub);
        
        $cameras[] = [
            'bord' => $bord,
            'navn' => $navn,
            'type' => $type,
            'ip' => $ip,
            'rtsp' => $rtsp,
            'device' => $device,
            'resolution' => $res
        ];
        writeData('cameras_' . $klub, $cameras);
        $msg = "Kamera tilføjet: {$navn} (Bord {$bord})";
    } else {
        $msg = "Fejl: Udfyld venligst alle påkrævede felter for den valgte kameratype.";
    }
}

// Håndter sletning af kamera
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_camera'])) {
    $id = (int)$_POST['id'];
    $cameras = readData('cameras_' . $klub);
    $newCameras = [];
    foreach ($cameras as $index => $cam) {
        if ($index !== $id) {
            $newCameras[] = $cam;
        }
    }
    writeData('cameras_' . $klub, $newCameras);
    $msg = "Kamera slettet.";
}

// Hent kameraer for den aktuelle klub
$myCameras = readData('cameras_' . $klub);
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kameraer — <?= htmlspecialchars($klubNavn) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Nunito Sans',Arial,sans-serif; background:#0a0a0a; color:#e0e0e0; min-height:100vh; }
        .nav { background:#111; border-bottom:1px solid #1a3a2a; padding:.8rem 2rem; display:flex; justify-content:space-between; align-items:center; }
        .nav-brand { color:#00ff41; font-size:1.1rem; font-weight:700; letter-spacing:.1em; }
        .nav-links { display:flex; align-items:center; gap:1.5rem; }
        .nav-user { color:#888; font-size:.85rem; }
        .nav-link { color:#e0e0e0; text-decoration:none; font-size:.85rem; transition:color .2s; }
        .nav-link:hover { color:#00ff41; }
        .nav-link.active { color:#00ff41; font-weight:700; border-bottom:2px solid #00ff41; }
        .nav-user a { color:#ff4444; text-decoration:none; margin-left:1rem; font-size:.8rem; }
        
        main { max-width:1000px; margin:0 auto; padding:2rem; }
        h1 { font-size:1.3rem; color:#00ff41; margin-bottom:1.5rem; font-weight:400; letter-spacing:.05em; }
        
        .msg { background:#0a1a0a; border:1px solid #004411; color:#00ff41; padding:.6rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:.85rem; }
        
        .admin-grid { display:grid; grid-template-columns: 1fr 320px; gap:2rem; }
        @media (max-width: 768px) { .admin-grid { grid-template-columns: 1fr; } }

        /* Camera List */
        .cam-list { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:1rem; }
        .card { background:#111; border:1px solid #1a3a2a; border-radius:12px; padding:1.5rem; position:relative; }
        .card h3 { color:#e0e0e0; font-size:1rem; margin-bottom:.5rem; }
        .card .details { color:#666; font-size:.85rem; line-height:1.6; margin-bottom:1rem; }
        .card .detail-row { display:flex; justify-content:space-between; border-bottom:1px solid #1a1a1a; padding:2px 0; }
        .card .detail-label { color:#888; }

        /* Form */
        .form-container { background:#111; border:1px solid #1a3a2a; border-radius:12px; padding:1.5rem; height:fit-content; position:sticky; top:2rem; }
        .form-container h2 { color:#00ff41; font-size:1rem; margin-bottom:1rem; letter-spacing:.05em; }
        .field { margin-bottom:1rem; }
        .field label { display:block; color:#888; font-size:.75rem; margin-bottom:.3rem; text-transform:uppercase; }
        .field input, .field select { width:100%; background:#0a0a0a; border:1px solid #333; color:#fff; padding:.6rem; border-radius:6px; font-family:inherit; font-size:.85rem; }
        .field input:focus, .field select:focus { outline:none; border-color:#00ff41; }
        
        .btn { cursor:pointer; font-family:inherit; transition:all .2s; }
        .btn-add { width:100%; background:#00ff41; color:#0a0a0a; border:none; padding:.7rem; border-radius:8px; font-size:.85rem; font-weight:600; margin-top:0.5rem; }
        .btn-add:hover { background:#00cc33; box-shadow: 0 0 10px rgba(0,255,65,0.3); }
        .btn-delete { background:#441111; color:#ff4444; border:1px solid #661111; padding:.4rem .8rem; border-radius:6px; font-size:.75rem; cursor:pointer; }
        .btn-delete:hover { background:#661111; color:#fff; }

        .empty-state { text-align:center; padding:3rem; background:#111; border:2px dashed #1a3a2a; border-radius:12px; color:#666; font-style:italic; }
        
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="nav">
        <span class="nav-brand">🎱 BILLARD STREAM</span>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="cameras.php" class="nav-link active">Kameraer</a>
            <span class="nav-user"><?= htmlspecialchars($klubNavn) ?> <a href="logout.php">[log ud]</a></span>
        </div>
    </div>

    <main>
        <h1>⚙️ Kamera Administration — <?= htmlspecialchars($klubNavn) ?></h1>

        <?php if ($msg): ?>
            <div class="msg"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="admin-grid">
            <!-- Camera List -->
            <div class="list-section">
                <?php if (empty($myCameras)): ?>
                    <div class="empty-state">
                        Ingen kameraer konfigureret. Tilføj dit første kamera i formularen til højre.
                    </div>
                <?php else: ?>
                    <div class="cam-list">
                        <?php foreach ($myCameras as $id => $cam): ?>
                            <div class="card">
                                <h3>Bord <?= htmlspecialchars($cam['bord']) ?>: <?= htmlspecialchars($cam['navn']) ?></h3>
                                <div class="details">
                                    <?php if (($cam['type'] ?? 'ip') === 'ip'): ?>
                                        <div class="detail-row"><span class="detail-label">Kilde</span><span><?= htmlspecialchars($cam['rtsp'] ?? $cam['ip'] ?? 'IP Kamera') ?></span></div>
                                    <?php elseif (($cam['type'] ?? 'ip') === 'usb'): ?>
                                        <div class="detail-row"><span class="detail-label">Enhedssti</span><span><?= htmlspecialchars($cam['device']) ?></span></div>
                                    <?php elseif (($cam['type'] ?? 'ip') === 'builtin'): ?>
                                        <div class="detail-row"><span class="detail-label">Kilde</span><span>Indbygget (Builtin)</span></div>
                                    <?php endif; ?>
                                    <div class="detail-row"><span class="detail-label">Type / Opløsning</span><span><?= htmlspecialchars($cam['type'] ?? 'ip') ?> — <?= htmlspecialchars($cam['opløsning'] ?? $cam['resolution'] ?? '1080p') ?></span></div>
                                </div>
                                <form method="post" onsubmit="return confirm('Er du sikker på at du vil slette dette kamera?');">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" name="delete_camera" class="btn btn-delete">🗑 Slet</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add Camera Form -->
            <div class="form-container">
                <h2>Tilføj Kamera</h2>
                <form method="post" id="cameraForm">
                    <div class="field">
                        <label>Bord Nummer</label>
                        <input type="number" name="bord" required placeholder="f.eks. 1">
                    </div>
                    <div class="field">
                        <label>Kamera Navn</label>
                        <input type="text" name="navn" required placeholder="f.eks. Nord-bord 1">
                    </div>
                    <div class="field">
                        <label>Type</label>
                        <select name="type" id="typeSelect" onchange="toggleFields()">
                            <option value="ip">IP Kamera</option>
                            <option value="usb">USB Kamera</option>
                            <option value="builtin">Indbygget</option>
                        </select>
                    </div>
                    <div class="field" id="field_ip">
                        <label>IP Adresse</label>
                        <input type="text" name="ip" placeholder="f.eks. 192.168.1.50">
                    </div>
                    <div class="field" id="field_rtsp">
                        <label>RTSP URL</label>
                        <input type="text" name="rtsp" placeholder="rtsp://user:pass@ip:554/stream">
                    </div>
                    <div class="field hidden" id="field_usb">
                        <label>Enhedssti</label>
                        <input type="text" name="device" placeholder="f.eks. /dev/video0">
                    </div>
                    <div class="field">
                        <label>Opløsning</label>
                        <select name="resolution">
                            <option value="720p">720p</option>
                            <option value="1080p" selected>1080p</option>
                            <option value="4K">4K</option>
                        </select>
                    </div>
                    <button type="submit" name="add_camera" class="btn btn-add">➕ TILFØJ KAMERA</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function toggleFields() {
            const type = document.getElementById('typeSelect').value;
            const ipField = document.getElementById('field_ip');
            const rtspField = document.getElementById('field_rtsp');
            const usbField = document.getElementById('field_usb');

            ipField.classList.add('hidden');
            rtspField.classList.add('hidden');
            usbField.classList.add('hidden');

            if (type === 'ip') {
                ipField.classList.remove('hidden');
                rtspField.classList.remove('hidden');
            } else if (type === 'usb') {
                usbField.classList.remove('hidden');
            }
        }
        // Initialize fields on load
        window.onload = toggleFields;
    </script>
</body>
</html>
