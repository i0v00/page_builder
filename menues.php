<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
header('Content-Type: text/html; charset=utf-8');

$message = '';
$messageType = '';

// Obtener todos los menús principales y sus submenús
$mainMenus = getMenuItems($pdo);
$allMenus = $pdo->query("SELECT * FROM menu_items ORDER BY sort_order ASC, name ASC")->fetchAll();

// ========== CREAR MENÚ ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create'])) {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $url = trim($_POST['url'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if (!$name || !$slug) {
        $message = 'Nombre y slug son requeridos';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO menu_items (name, slug, parent_id, url, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $parentId, $url, $icon, $sortOrder]);
            $message = 'Menú creado exitosamente';
            $messageType = 'success';
            header('Location: /jdeconomy/menus/?msg=created');
            exit;
        } catch (PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// ========== EDITAR MENÚ ==========
$editMenu = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editMenu = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update'])) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $url = trim($_POST['url'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if (!$name || !$slug) {
        $message = 'Nombre y slug son requeridos';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE menu_items SET name=?, slug=?, parent_id=?, url=?, icon=?, sort_order=? WHERE id=?");
            $stmt->execute([$name, $slug, $parentId, $url, $icon, $sortOrder, $id]);
            $message = 'Menú actualizado exitosamente';
            $messageType = 'success';
            header('Location: /jdeconomy/menus/?msg=updated');
            exit;
        } catch (PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// ========== ELIMINAR MENÚ ==========
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([(int)$_GET['delete']]);
        $message = 'Menú eliminado exitosamente';
        $messageType = 'success';
        header('Location: /jdeconomy/menus/?msg=deleted');
        exit;
    } catch (PDOException $e) {
        $message = 'Error al eliminar: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener menús principales para el dropdown de padre
$parentOptions = getMenuItems($pdo, null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD de Menús | JDEconomy Academia</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/jdeconomy/assets/css/style.css">
</head>
<body>
    <div class="icon-defs">
        <svg id="icon-home" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        <svg id="icon-list" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 16H6v-2h12v2zm0-3H6V9h12v10zm0-13H6V6h12v7z"/></svg>
        <svg id="icon-plus" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/></svg>
        <svg id="icon-edit" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
        <svg id="icon-delete" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        <svg id="icon-arrow-right" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        <svg id="icon-sun" viewBox="0 0 24 24"><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2zm-1-12c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zM11 20H2c-.55 0-1 .45-1 1s.45 1 1 1h9c.55 0 1-.45 1-1s-.45-1-1-1zM20.5 10c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5S18.17 13 19 13s1.5-.67 1.5-1.5zM12 2c-3.86 0-7 3.14-7 7s3.14 7 7 7 7-3.14 7-7-3.14-7-7-7z"/></svg>
        <svg id="icon-moon" viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-3.3 0-6-2.7-6-6 0-1.21.32-2.33.88-3.28 1.17-1.16 2.62-1.84 4.2-1.98-.43-1.18-1.51-2-2.82-2zm-1 15c-3.3 0-6-2.7-6-6 0-2.09 1.23-3.9 3-4.76.34.28.54.7.54 1.16 0 1.66 1.34 3 3 3s3-1.34 3-3-1.34-3-3-3c-2.21 0-4 1.79-4 4 0 .57.19 1.09.46 1.54-.92 1.25-1.46 2.73-1.46 4.24 0 3.31 2.69 6 6 6zm11-5c-.47 0-.93-.09-1.37-.25-.14-.16-.21-.39-.21-.63 0-.6.45-1.08 1.05-1.18 1.36-.22 2.44-1.18 2.93-2.58.49-1.41.49-2.95 0-4.36-.49-1.4-1.57-2.36-2.93-2.58-.6-.1-1.05.58-1.05 1.18 0 .24-.07.47-.21.63-.44.16-.9.25-1.37.25-2.2 0-4-1.8-4-4 0-1.1.46-2.08 1.19-2.78.8-.79 1.8-1.26 2.81-1.26 2.2 0 4 1.8 4 4s-1.8 4-4 4z"/></svg>
        <svg id="icon-close" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    </div>

    <header class="header" id="main-header">
        <div class="header-inner">
            <div class="logo">
                <div class="logo-mark">JD</div>
                <div class="logo-text">JDEconomy <span style="font-weight:400; font-size:0.85rem; opacity:0.7;">Academia</span></div>
            </div>
            <nav class="nav-menu" id="nav-menu">
                <a href="/jdeconomy/" class="nav-link">
                    <span class="icon icon-sm"><svg><use href="#icon-home"></use></svg></span>
                    Volver a Academia
                </a>
                <a href="/jdeconomy/ai-work/" class="nav-link">
                    <span class="icon icon-sm"><svg><use href="#icon-list"></use></svg></span>
                    AI Work
                </a>
            </nav>
            <div class="nav-actions">
                <button class="theme-toggle" id="theme-toggle" aria-label="Cambiar tema">
                    <span class="theme-icon">
                        <svg id="theme-sun" width="18" height="18"><use href="#icon-sun"></use></svg>
                        <svg id="theme-moon" width="18" height="18" style="display:none"><use href="#icon-moon"></use></svg>
                    </span>
                </button>
                <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Menú">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <main class="ai-work-container">
        <header class="page-header">
            <h1>📋 CRUD de Menús</h1>
            <p>Gestiona los menús y submenús de la academia. Estos se usan en el panel AI Work y en el sidebar superior.</p>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-section">
                <h2><span class="icon icon-sm"><svg><use href="#icon-plus"></use></svg></span> <?php echo $editMenu ? 'Editar Menú' : 'Crear Nuevo Menú'; ?></h2>
                <form method="POST" action="">
                    <?php if ($editMenu): ?>
                        <input type="hidden" name="id" value="<?= $editMenu['id'] ?>">
                        <input type="hidden" name="action_update" value="1">
                    <?php else: ?>
                        <input type="hidden" name="action_create" value="1">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="name">Nombre del Menú <span style="color: var(--color-primary);">*</span></label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($editMenu['name'] ?? '') ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="slug">Slug <span style="color: var(--color-primary);">*</span></label>
                            <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($editMenu['slug'] ?? '') ?>" placeholder="ej: servicios" required>
                            <small>Solo letras minúsculas, números y guiones</small>
                        </div>
                        <div class="form-field">
                            <label for="parent_id">Menú Padre</label>
                            <select id="parent_id" name="parent_id">
                                <option value="">(Menú principal - Sidebar superior)</option>
                                <?php foreach ($parentOptions as $parent): ?>
                                    <option value="<?= $parent['id'] ?>" <?= (!empty($editMenu) && $editMenu['parent_id'] == $parent['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($parent['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>Si seleccionas uno, este será un submenú</small>
                        </div>
                        <div class="form-field">
                            <label for="url">URL</label>
                            <input type="text" id="url" name="url" value="<?= htmlspecialchars($editMenu['url'] ?? '') ?>" placeholder="/jdeconomy/rutas/">
                            <small>URL completa de la página</small>
                        </div>
                        <div class="form-field">
                            <label for="icon">Icono (CSS class)</label>
                            <input type="text" id="icon" name="icon" value="<?= htmlspecialchars($editMenu['icon'] ?? '') ?>" placeholder="icon-home">
                            <small>Clase CSS del icono (opcional)</small>
                        </div>
                        <div class="form-field">
                            <label for="sort_order">Orden</label>
                            <input type="number" id="sort_order" name="sort_order" value="<?= htmlspecialchars($editMenu['sort_order'] ?? 0) ?>" min="0">
                        </div>
                    </div>

                    <div style="display: flex; gap: var(--space-md); margin-top: var(--space-lg);">
                        <button type="submit" class="btn-primary"><?php echo $editMenu ? 'Actualizar' : 'Crear'; ?></button>
                        <?php if ($editMenu): ?>
                            <a href="/jdeconomy/menus/" class="btn-outline">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="form-card">
            <div class="form-section">
                <h2><span class="icon icon-sm"><svg><use href="#icon-list"></use></svg></span> Menús Existentes</h2>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; margin-top: var(--space-md);">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--color-borders);">
                                <th style="text-align: left; padding: var(--space-md); color: var(--color-text-secondary); font-size: 0.875rem;">Nombre</th>
                                <th style="text-align: left; padding: var(--space-md); color: var(--color-text-secondary); font-size: 0.875rem;">Slug</th>
                                <th style="text-align: left; padding: var(--space-md); color: var(--color-text-secondary); font-size: 0.875rem;">Padre</th>
                                <th style="text-align: left; padding: var(--space-md); color: var(--color-text-secondary); font-size: 0.875rem;">URL</th>
                                <th style="text-align: left; padding: var(--space-md); color: var(--color-text-secondary); font-size: 0.875rem;">Orden</th>
                                <th style="text-align: right; padding: var(--space-md); color: var(--color-text-secondary); font-size: 0.875rem;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mainMenus as $menu): ?>
                                <tr style="border-bottom: 1px solid var(--color-borders);">
                                    <td style="padding: var(--space-md); color: var(--color-text-main); font-weight: 600;">
                                        <span class="icon icon-sm" style="margin-right: var(--space-sm);"><svg><use href="#icon-home"></use></svg></span>
                                        <?= htmlspecialchars($menu['name']) ?>
                                    </td>
                                    <td style="padding: var(--space-md); color: var(--color-text-secondary); font-family: monospace;">
                                        <?= htmlspecialchars($menu['slug']) ?>
                                    </td>
                                    <td style="padding: var(--space-md); color: var(--color-text-secondary);">Principal</td>
                                    <td style="padding: var(--space-md); color: var(--color-text-secondary);">
                                        <a href="<?= htmlspecialchars($menu['url']) ?>" style="color: var(--color-primary);"><?= htmlspecialchars($menu['url']) ?></a>
                                    </td>
                                    <td style="padding: var(--space-md); color: var(--color-text-secondary);"><?= $menu['sort_order'] ?></td>
                                    <td style="padding: var(--space-md); text-align: right;">
                                        <a href="/jdeconomy/menus/?edit=<?= $menu['id'] ?>" class="btn-outline" style="padding: var(--space-xs) var(--space-sm); font-size: 0.85rem;">Editar</a>
                                        <a href="/jdeconomy/menus/?delete=<?= $menu['id'] ?>" class="btn-outline" style="padding: var(--space-xs) var(--space-sm); font-size: 0.85rem; color: #dc2626;" onclick="return confirm('¿Eliminar este menú y todos sus submenús?')">Eliminar</a>
                                    </td>
                                </tr>
                                <?php
                                // Submenús
                                $children = array_filter($allMenus, fn($m) => $m['parent_id'] == $menu['id']);
                                foreach ($children as $child): ?>
                                <tr style="border-bottom: 1px solid var(--color-borders); background-color: rgba(107, 30, 42, 0.02);">
                                    <td style="padding: var(--space-md); color: var(--color-text-main); font-weight: 500;">
                                        <span class="icon icon-sm" style="margin-right: var(--space-sm); color: var(--color-primary);"><svg><use href="#icon-arrow-right"></use></svg></span>
                                        <?= htmlspecialchars($child['name']) ?>
                                    </td>
                                    <td style="padding: var(--space-md); color: var(--color-text-secondary); font-family: monospace;">
                                        <?= htmlspecialchars($child['slug']) ?>
                                    </td>
                                    <td style="padding: var(--space-md); color: var(--color-text-secondary);"><?= htmlspecialchars($menu['name']) ?></td>
                                    <td style="padding: var(--space-md); color: var(--color-text-secondary);">
                                        <a href="<?= htmlspecialchars($child['url']) ?>" style="color: var(--color-primary);"><?= htmlspecialchars($child['url']) ?></a>
                                    </td>
                                    <td style="padding: var(--space-md); color: var(--color-text-secondary);"><?= $child['sort_order'] ?></td>
                                    <td style="padding: var(--space-md); text-align: right;">
                                        <a href="/jdeconomy/menus/?edit=<?= $child['id'] ?>" class="btn-outline" style="padding: var(--space-xs) var(--space-sm); font-size: 0.85rem;">Editar</a>
                                        <a href="/jdeconomy/menus/?delete=<?= $child['id'] ?>" class="btn-outline" style="padding: var(--space-xs) var(--space-sm); font-size: 0.85rem; color: #dc2626;" onclick="return confirm('¿Eliminar este submenú?')">Eliminar</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($mainMenus)): ?>
                    <p style="color: var(--color-text-secondary); text-align: center; padding: var(--space-xl);">No hay menús creados aún.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">JDEconomy Academia</div>
            <div class="footer-text">CRUD de Menús &copy; 2026 JDEconomy</div>
        </div>
    </footer>

    <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>
    <div class="mobile-menu" id="mobile-menu">
        <button class="mobile-close" id="mobile-close" aria-label="Cerrar">
            <svg width="20" height="20"><use href="#icon-close"></use></svg>
        </button>
        <nav class="mobile-nav">
            <div class="mobile-menu-item">
                <a href="/jdeconomy/" class="mobile-link">Volver a Academia</a>
            </div>
            <div class="mobile-menu-item">
                <a href="/jdeconomy/ai-work/" class="mobile-link">AI Work</a>
            </div>
        </nav>
    </div>

    <script src="/jdeconomy/assets/js/app.js"></script>
</body>
</html>