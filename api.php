<?php
header('Content-Type: application/json');
require 'pdo.php';

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $pdo->beginTransaction();
    try {
        // Create user if not exists
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (unique_id) VALUES (?)");
        $stmt->execute([$data['user_id']]);

        // Create Bundle
        $stmt = $pdo->prepare("INSERT INTO bundles (user_id, bundle_name, slug) VALUES (?, ?, ?)");
        $stmt->execute([$data['user_id'], $data['name'], $data['slug']]);
        $bundleId = $pdo->lastInsertId();

        // Create Links
        $stmt = $pdo->prepare("INSERT INTO bundle_links (bundle_id, link_title, destination_url) VALUES (?, ?, ?)");
        foreach ($data['links'] as $link) {
            $stmt->execute([$bundleId, $link['title'], $link['url']]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

if ($action === 'list') {
    $uid = $_GET['user_id'] ?? '';
    $stmt = $pdo->prepare("SELECT b.*, (SELECT COUNT(*) FROM bundle_links WHERE bundle_id = b.id) as link_count 
                           FROM bundles b WHERE b.user_id = ? ORDER BY b.created_at DESC");
    $stmt->execute([$uid]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    ALTER TABLE bundles ADD status ENUM('active','inactive') DEFAULT 'active';
}
if ($action === 'view') {
    $slug = $_GET['slug'] ?? '';

    if (empty($slug)) {
        echo json_encode(['success' => false, 'error' => 'Slug required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM bundles WHERE slug = ?");
    $stmt->execute([$slug]);
    $bundle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bundle) {
        echo json_encode(['success' => false, 'error' => 'Bundle not found']);
        exit;
        if ($action === 'view') {
    $slug = $_GET['slug'] ?? '';

    if (empty($slug)) {
        echo json_encode(['success' => false, 'error' => 'Slug required']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        // Get bundle
        $stmt = $pdo->prepare("SELECT * FROM bundles WHERE slug = ? AND status = 'active'");
        $stmt->execute([$slug]);
        $bundle = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bundle) {
            echo json_encode(['success' => false, 'error' => 'Bundle not found or inactive']);
            exit;
        }

        // Increase view count
        $stmt = $pdo->prepare("UPDATE bundles SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$bundle['id']]);

        // Get links
        $stmt = $pdo->prepare("SELECT link_title, destination_url FROM bundle_links WHERE bundle_id = ?");
        $stmt->execute([$bundle['id']]);
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'bundle' => $bundle,
            'links' => $links
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

    }

    $stmt = $pdo->prepare("SELECT link_title, destination_url FROM bundle_links WHERE bundle_id = ?");
    $stmt->execute([$bundle['id']]);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'bundle' => $bundle,
        'links' => $links
    ]);
}
