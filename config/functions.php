<?php
// JDEconomy Academia - Funciones Helper
require_once __DIR__ . '/db.php';

function getMenuItems($pdo, $parentId = null) {
    if ($parentId === null) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order ASC, name ASC");
        $stmt->execute([]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$parentId]);
    }
    return $stmt->fetchAll();
}

function getAllMenuItems($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getMenuItemBySlug($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getCoursesByMenu($pdo, $menuId) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE menu_id = ? AND is_published = 1 ORDER BY sort_order ASC, title ASC");
    $stmt->execute([$menuId]);
    return $stmt->fetchAll();
}

function getCourseBySlug($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT c.*, m.name as menu_name, m.slug as menu_slug FROM courses c LEFT JOIN menu_items m ON c.menu_id = m.id WHERE c.slug = ? AND c.is_published = 1 LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getAllCourses($pdo) {
    $stmt = $pdo->prepare("SELECT c.*, m.name as menu_name, m.slug as menu_slug FROM courses c LEFT JOIN menu_items m ON c.menu_id = m.id WHERE c.is_published = 1 ORDER BY c.sort_order ASC, c.title ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getCourseCountByMenu($pdo, $menuId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE menu_id = ? AND is_published = 1");
    $stmt->execute([$menuId]);
    return $stmt->fetchColumn();
}