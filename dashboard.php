<?php
/**
 * Billard Stream — Dashboard
 */
require_once __DIR__ . '/config.php';
requireLogin();

$klub = $_SESSION['klub_navn'];
?><!DOCTYPE html>
<html lang="da">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billard Stream — <?= htmlspecialchars($klub) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Nunito Sans',Arial,sans-serif; background:#0a0a0a; color:#e0e0e0; min-height:100vh; }
/* Nav */
.nav { background:#111; border-bottom:1px solid #1a3a2a; padding:.8rem 2rem; display:flex; justify-content:space-between; align-items:center; }
.nav-brand { color:#00ff41; font-size:1.1rem; font-weight:700; letter-spacing:.1em; }
.nav-user { color:#888; font-size:.85rem; }
.nav-user a { color:#ff4444; text-decoration:none; margin-left:1rem; font-size:.8rem; }
/* Main */
main { max-width:1000px; margin:0 auto; padding:2rem; }
h1 { font-size:1.3rem; color:#00ff41; margin-bottom:1.5rem; font-weight:400; letter-spacing:.05em; }
/* Bord cards */
.grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:1rem; }
.card { background:#111; border:1px solid #1a3a2a; border-radius:12px; padding:1.5rem; }
.card h3 { color:#e0e0e0; font-size:1rem; margin-bottom:.3rem; }
.card .cam { color:#666; font-size:.8rem; margin-bottom:1rem; }
.status-off { display:inline-block; padding:.25rem .6rem; border-radius:20px; font-size:.75rem; background:#1a0a0a; color:#ff4444; border:1px solid #441111; }
.status-on { display:inline-block; padding:.25rem .6rem; border-radius:20px; font-size:.75rem; background:#0a1a0a; color:#00ff41; border:1px solid #004411; }
.btn-start { background:#00ff41; color:#0a0a0a; border:none; padding:.5rem 1.5rem; border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; width:100%; margin-top:.8rem; }
.btn-start:hover { background:#00cc33; }
.btn-stop { background:#441111; color:#ff4444; border:1px solid #661111; padding:.5rem 1.5rem; border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; width:100%; margin-top:.8rem; }
.btn-stop:hover { background:#661111; }
</style>
</head>
<body>
<div class="nav">
    <span class="nav-brand">🎱 BILLARD STREAM</span>
    <span class="nav-user"><?= htmlspecialchars($klub) ?> <a href="logout.php">[log ud]</a></span>
</div>
<main>
    <h1>🟢 Streams — <?= htmlspecialchars($klub) ?></h1>
    
    <div class="grid">
        <div class="card">
            <h3>Bord 1</h3>
            <p class="cam">IP Camera 1 · 1080p</p>
            <span class="status-off">● slukket</span>
            <button class="btn-start">▶ START STREAM</button>
        </div>
        <div class="card">
            <h3>Bord 2</h3>
            <p class="cam">IP Camera 2 · 1080p</p>
            <span class="status-off">● slukket</span>
            <button class="btn-start">▶ START STREAM</button>
        </div>
        <div class="card">
            <h3>Bord 3</h3>
            <p class="cam">IP Camera 3 · 720p</p>
            <span class="status-off">● slukket</span>
            <button class="btn-start">▶ START STREAM</button>
        </div>
        <div class="card">
            <h3>Bord 4</h3>
            <p class="cam">IP Camera 4 · 720p</p>
            <span class="status-off">● slukket</span>
            <button class="btn-start">▶ START STREAM</button>
        </div>
    </div>
</main>
</body>
</html>
