<?php
/**
 * Billard Stream — Polling API (JSON-fil baseret)
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Klienten henter kommando
    $klub = $_GET['klub'] ?? '';
    $bord = (int)($_GET['bord'] ?? 0);
    
    if (!$klub || !$bord) {
        echo json_encode(["cmd" => null]);
        exit;
    }
    
    $cmds = readData('commands_' . $klub);
    if (!$cmds) $cmds = [];
    
    $found = null;
    $keep = [];
    foreach ($cmds as $c) {
        if ($c['bord'] == $bord && ($c['status'] ?? '') === 'pending' && !$found) {
            $found = $c;
            $found['status'] = 'done';
        }
        $keep[] = $c;
    }
    
    if ($found) {
        // Hent kamera-data for at få type
        $cameras = readData('cameras_' . $klub);
        $type = 'ip';
        $rtsp = $found['rtsp'] ?? '';
        $device = '';
        if ($cameras) {
            foreach ($cameras as $cam) {
                if (($cam['bord'] ?? 0) == $bord) {
                    $type = $cam['type'] ?? 'ip';
                    $rtsp = $cam['rtsp'] ?? $rtsp;
                    $device = $cam['device'] ?? '';
                    break;
                }
            }
        }
        
        writeData('commands_' . $klub, $keep);
        echo json_encode([
            "cmd" => $found['cmd'],
            "type" => $type,
            "rtsp" => $rtsp,
            "device" => $device,
            "rtmp" => $found['rtmp'] ?? '',
            "title" => $found['title'] ?? '',
            "bord" => $bord
        ]);
    } else {
        echo json_encode(["cmd" => null]);
    }
    
} elseif ($method === 'POST') {
    // Klienten sender status
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $klub = $input['klub'] ?? '';
    $bord = (int)($input['bord'] ?? 0);
    $status = $input['status'] ?? '';
    
    if ($klub && $bord) {
        $statuses = readData('status_' . $klub);
        if (!$statuses) $statuses = [];
        
        // Opdater eller tilføj
        $found = false;
        foreach ($statuses as &$s) {
            if ($s['bord'] == $bord) {
                $s['status'] = $status;
                $s['updated'] = date('c');
                $found = true;
                break;
            }
        }
        if (!$found) {
            $statuses[] = ['bord' => $bord, 'status' => $status, 'updated' => date('c')];
        }
        writeData('status_' . $klub, $statuses);
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Missing klub or bord"]);
    }
}
