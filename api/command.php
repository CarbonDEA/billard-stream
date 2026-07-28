<?php
require_once '../config.php';

header('Content-Type: application/json');

$db = getDB();

// 1 & 2. Initialize Tables
$initQueries = [
    "CREATE TABLE IF NOT EXISTS bs_commands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        klub VARCHAR(50) NOT NULL,
        bord INT NOT NULL,
        cmd VARCHAR(20) NOT NULL,
        rtsp TEXT,
        rtmp TEXT,
        title VARCHAR(255),
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS bs_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        klub VARCHAR(50) NOT NULL,
        bord INT NOT NULL,
        status VARCHAR(20) NOT NULL,
        message TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `klub_bord` (`klub`, `bord`)
    )"
];

foreach ($initQueries as $query) {
    $db->exec($query);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 3. GET /api/command.php?klub=...&bord=...
    $klub = $_GET['klub'] ?? null;
    $bord = $_GET['bord'] ?? null;

    if (!$klub || !$bord) {
        echo json_encode(["error" => "Missing klub or bord"]);
        exit;
    }

    // Fetch pending command
    $stmt = $db->prepare("SELECT * FROM bs_commands WHERE klub = ? AND bord = ? AND status = 'pending' ORDER BY created_at ASC LIMIT 1");
    $stmt->execute([$klub, (int)$bord]);
    $cmd = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cmd) {
        // Mark as done
        $upd = $db->prepare("UPDATE bs_commands SET status = 'done' WHERE id = ?");
        $upd->execute([$cmd['id']]);

        echo json_encode([
            "cmd" => $cmd['cmd'],
            "rtsp" => $cmd['rtsp'],
            "rtmp" => $cmd['rtmp'],
            "title" => $cmd['title']
        ]);
    } else {
        echo json_encode(["cmd" => null]);
    }

} elseif ($method === 'POST') {
    // 4. POST /api/command.php (JSON body)
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['klub'], $input['bord'], $input['status'])) {
        echo json_encode(["success" => false, "error" => "Invalid input"]);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO bs_status (klub, bord, status, message) 
                          VALUES (?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE status = VALUES(status), message = VALUES(message)");
    
    $success = $stmt->execute([
        $input['klub'],
        (int)$input['bord'],
        $input['status'],
        $input['message'] ?? ''
    ]);

    echo json_encode(["success" => $success]);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
