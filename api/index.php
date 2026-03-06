<?php
// php_project/api/index.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/DramaBox.php';

$action = $_GET['action'] ?? '';

if ($action === 'latest') {
    $page = (int)($_GET['page'] ?? 1);
    $res = DramaBox::fetchLatest($page);

    $records = [];
    $raw = $res['data']['data']['newTheaterList']['records'] ?? [];
    foreach ($raw as $r) {
        $records[] = DramaBox::mapToItem($r);
    }

    echo json_encode(['records' => $records]);
    exit;
}

if ($action === 'search') {
    $q = $_GET['q'] ?? '';
    if (empty($q)) {
        echo json_encode(['records' => []]);
        exit;
    }

    $res = DramaBox::fetchSuggest($q);
    $records = [];
    $raw = $res['data']['data']['suggestList'] ?? [];
    foreach ($raw as $r) {
        $records[] = DramaBox::mapToItem($r);
    }

    echo json_encode(['records' => $records]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
