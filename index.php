<?php
/**
 * Billard Stream — Landing page
 */
require_once __DIR__ . '/config.php';
$loggedIn = isLoggedIn();
?><!DOCTYPE html>
<html lang="da">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billard Stream — Streaming til billardklubber</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Nunito Sans',Arial,sans-serif; background:#0a0a0a; color:#e0e0e0; }
/* Hero */
.hero { text-align:center; padding:5rem 2rem 4rem; background: linear-gradient(180deg, #0a0a0a 0%, #0d1a0d 100%); border-bottom:1px solid #1a3a2a; }
.hero h1 { font-size:3rem; color:#00ff41; letter-spacing:.15em; margin-bottom:.5rem; }
.hero .sub { color:#888; font-size:1.1rem; margin-bottom:2rem; }
.hero .btn { display:inline-block; padding:.8rem 2.5rem; border-radius:8px; text-decoration:none; font-weight:600; margin:.3rem; }
.btn-primary { background:#00ff41; color:#0a0a0a; }
.btn-ghost { border:1px solid #00ff41; color:#00ff41; background:transparent; }
/* Features */
.features { max-width:1000px; margin:0 auto; padding:4rem 2rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:2rem; }
.feat { background:#111; border:1px solid #1a3a2a; border-radius:12px; padding:1.5rem; text-align:center; }
.feat .icon { font-size:2rem; margin-bottom:.5rem; }
.feat h3 { color:#00ff41; margin-bottom:.5rem; font-size:1rem; }
.feat p { color:#888; font-size:.85rem; line-height:1.5; }
/* How it works */
.how { max-width:800px; margin:0 auto; padding:4rem 2rem; }
.how h2 { color:#00ff41; text-align:center; margin-bottom:2rem; font-weight:400; font-size:1.4rem; }
.steps { display:flex; flex-direction:column; gap:1rem; }
.step { display:flex; gap:1rem; align-items:flex-start; background:#111; border:1px solid #1a3a2a; border-radius:12px; padding:1.2rem; }
.step-num { color:#00ff41; font-size:1.5rem; font-weight:700; min-width:40px; }
.step-text h4 { color:#e0e0e0; margin-bottom:.2rem; }
.step-text p { color:#888; font-size:.85rem; }
/* Download */
.download { text-align:center; padding:4rem 2rem; background: linear-gradient(0deg, #0a0a0a 0%, #0d1a0d 100%); border-top:1px solid #1a3a2a; }
.download h2 { color:#00ff41; margin-bottom:.5rem; font-weight:400; }
.download p { color:#888; margin-bottom:1.5rem; }
.dl-links { display:flex; justify-content:center; gap:1rem; flex-wrap:wrap; }
.dl-btn { display:inline-flex; align-items:center; gap:.5rem; padding:.8rem 1.5rem; background:#111; border:1px solid #2a2a2a; border-radius:8px; color:#e0e0e0; text-decoration:none; transition:border .2s; }
.dl-btn:hover { border-color:#00ff41; }
.dl-btn .os { font-size:.75rem; color:#888; }
/* Footer */
.footer { text-align:center; padding:2rem; color:#444; font-size:.8rem; border-top:1px solid #111; }
.footer a { color:#666; text-decoration:none; }
</style>
</head>
<body>
<div class="hero">
    <h1>🎱 BILLARD STREAM</h1>
    <p class="sub">Tænd/sluk streaming til din billardklub — så enkelt som det bør være</p>
    <?php if ($loggedIn): ?>
        <a href="dashboard.php" class="btn btn-primary">▶ GÅ TIL DASHBOARD</a>
    <?php else: ?>
        <a href="login.php" class="btn btn-primary">▶ LOG IND</a>
        <a href="#download" class="btn btn-ghost">📥 Download klient</a>
    <?php endif; ?>
</div>

<div class="features">
    <div class="feat"><div class="icon">🎯</div><h3>Én knap</h3><p>Tryk Start — så streamer du. Tryk Stop — så stopper du. Lige så enkelt som at tænde for fjernsynet.</p></div>
    <div class="feat"><div class="icon">📡</div><h3>Flere borde</h3><p>Understøtter op til 16 kameraer på samme computer. Hvert bord sin egen stream på YouTube.</p></div>
    <div class="feat"><div class="icon">⏰</div><h3>Planlægning</h3><p>Sæt streams på autopilot — de starter og stopper automatisk. Ingen glemte slukkede streams.</p></div>
    <div class="feat"><div class="icon">🪟🐧</div><h3>Windows & Linux</h3><p>Klienten kører på både Windows og Linux. Én download, to platforme.</p></div>
    <div class="feat"><div class="icon">🎨</div><h3>Web overlays</h3><p>Indsæt scoreboards, logoer eller sponsorbannere via URL. Dit overlay, dit design.</p></div>
    <div class="feat"><div class="icon">🔗</div><h3>Portal</h3><p>Alle streams samlet på stream.billard.dk — seerne kender kun én adresse.</p></div>
</div>

<div class="how">
    <h2>Sådan virker det</h2>
    <div class="steps">
        <div class="step"><span class="step-num">1</span><div class="step-text"><h4>Opret konto</h4><p>Klubben opretter sig på få minutter. Du vælger hvor mange streams I har brug for.</p></div></div>
        <div class="step"><span class="step-num">2</span><div class="step-text"><h4>Download klienten</h4><p>Installér den lille klient på klubbens Windows- eller Linux-maskine. Den kører stille i baggrunden.</p></div></div>
        <div class="step"><span class="step-num">3</span><div class="step-text"><h4>Tilslut kameraer</h4><p>Indtast IP-adresserne på jeres kameraer i web-grænsefladen. Vælg opløsning pr. kamera.</p></div></div>
        <div class="step"><span class="step-num">4</span><div class="step-text"><h4>Tilknyt YouTube</h4><p>Indsæt jeres YouTube stream keys (gratis — fås på få minutter i YouTube Studio).</p></div></div>
        <div class="step"><span class="step-num">5</span><div class="step-text"><h4>Stream!</h4><p>Tryk Start — eller sæt planlagte streams. Ethvert medlem kan gøre det. Ingen OBS, ingen kompliceret opsætning.</p></div></div>
    </div>
</div>

<div class="download" id="download">
    <h2>📥 Download klient</h2>
    <p>Klienten er på vej — kommer snart til både Windows og Linux</p>
    <div class="dl-links">
        <a href="#" class="dl-btn"><span>🪟</span> Windows <span class="os">.exe</span></a>
        <a href="client/stream-client.py" class="dl-btn"><span>🐧</span> Linux <span class="os">.py</span></a>
    </div>
    <div style="margin-top:1.5rem;background:#111;border:1px solid #1a3a2a;border-radius:8px;padding:1rem;max-width:600px;margin-left:auto;margin-right:auto;text-align:left">
        <p style="color:#888;font-size:.8rem;margin-bottom:.5rem">🐧 Linux — installation på én linje:</p>
        <div style="display:flex;gap:.5rem;align-items:stretch">
            <code id="installCmd" style="flex:1;background:#0a0a0a;padding:.8rem;border-radius:6px;color:#00ff41;font-size:.8rem;word-break:break-all">curl -sSL https://www.wahl-it.dk/billard-stream/install.txt | bash</code>
            <button onclick="copyCmd()" style="background:#1a3a2a;border:none;border-radius:6px;color:#00ff41;padding:.5rem .8rem;cursor:pointer;font-size:1.1rem" title="Kopiér">📋</button>
        </div>
    </div>
    <script>function copyCmd(){navigator.clipboard.writeText(document.getElementById('installCmd').textContent).then(()=>{let b=event.target;let o=b.textContent;b.textContent='✅';setTimeout(()=>b.textContent=o,1500)})}</script>
</div>

<div class="footer">
    <p>Wahl-IT Development &amp; Research · <a href="https://www.wahl-it.dk">wahl-it.dk</a></p>
</div>
</body>
</html>
