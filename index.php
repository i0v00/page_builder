<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
header('Content-Type: text/html; charset=utf-8');

$menuItems = getMenuItems($pdo);
$allCourses = getAllCourses($pdo);
$macroCourses = getCoursesByMenu($pdo, 3);
$ofimaticaCourses = getCoursesByMenu($pdo, 4);
$macroCount = getCourseCountByMenu($pdo, 3);
$ofimaticaCount = getCourseCountByMenu($pdo, 4);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JDEconomy Academia | Cursos de Macroeconomía y Ofimática</title>
    <meta name="description" content="Academia JDEconomy - Cursos profesionales de Macroeconomía y Ofimática. Formación de calidad para tu futuro.">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/jdeconomy/assets/css/style.css">
</head>
<body>
    <div class="icon-defs">
        <svg id="icon-home" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        <svg id="icon-book" viewBox="0 0 24 24"><path d="M18 22a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2zM2 22a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2zM4 6h4v12H4zM14 6h4v12h-4z"/></svg>
        <svg id="icon-chart" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14l-4-4h3l-2-2 2-2h3l-4 4z"/></svg>
        <svg id="icon-laptop" viewBox="0 0 24 24"><path d="M20 18c0 1.1-.9 2-2 2H6c-1.1 0-2-.9-2-2V2h16v16zm-8-2L4 6H2l8 8 8-8H12z"/></svg>
        <svg id="icon-users" viewBox="0 0 24 24"><path d="M16 11c0 4.42-3.58 8-8 8s-8-3.58-8-8 3.58-8 8-8 8 3.58 8 8m-1-5h-2c-.55 0-1-.45-1-1V7c0-.55.45-1 1-1h2c.55 0 1 .45 1 1v3c0 .55-.45 1-1 1zm-5.5 0c0-.32-.26-.58-.58-.58h-2c-.32 0-.58.26-.58.58v3c0 .32.26.58.58.58h2c.32 0 .58-.26.58-.58zM18 4h2c1.1 0 2 .9 2 2v1H18z"/></svg>
        <svg id="icon-mail" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        <svg id="icon-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        <svg id="icon-plus" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/></svg>
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
                <?php foreach ($menuItems as $item): ?>
                    <?php if ($item['parent_id'] === null): ?>
                        <?php
                        $children = array_filter($menuItems, fn($m) => $m['parent_id'] == $item['id']);
                        $hasChildren = !empty($children);
                        $courseCount = $hasChildren ? getCourseCountByMenu($pdo, $item['id']) : 0;
                        $iconId = match($item['slug']) {
                            'inicio' => 'icon-home',
                            'cursos' => 'icon-book',
                            'macroeconomia' => 'icon-chart',
                            'ofimatica' => 'icon-laptop',
                            'nosotros' => 'icon-users',
                            'contacto' => 'icon-mail',
                            default => 'icon-book'
                        };
                        ?>
                        <a href="<?= htmlspecialchars($item['url']) ?>" class="nav-link<?= $hasChildren ? ' has-children' : '' ?>" data-slug="<?= htmlspecialchars($item['slug']) ?>">
                            <span class="icon icon-sm"><svg><use href="#<?= $iconId ?>"></use></svg></span>
                            <?= htmlspecialchars($item['name']) ?>
                            <?php if ($hasChildren): ?>
                                <span class="nav-arrow">▾</span>
                            <?php elseif ($courseCount > 0): ?>
                                <span class="course-badge"><?= $courseCount ?> cursos</span>
                            <?php endif; ?>
                        </a>
                        <?php if ($hasChildren): ?>
                            <div class="dropdown" id="dropdown-<?= htmlspecialchars($item['slug']) ?>">
                                <div class="dropdown-inner">
                                    <?php foreach ($children as $child): ?>
                                        <a href="<?= htmlspecialchars($child['url']) ?>" class="dropdown-link">
                                            <span class="dropdown-dot"></span>
                                            <?= htmlspecialchars($child['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <span class="hero-badge">Academia JDEconomy</span>
            <h1>Formación Profesional<br><span class="text-gradient">en Macroeconomía y Ofimática</span></h1>
            <p>Cursos diseñados por expertos para impulsar tu carrera. Aprende macroeconomía, Excel, Word, PowerPoint, Access y Outlook con certificación.</p>
            <div class="hero-actions">
                <a href="/jdeconomy/cursos/" class="btn-primary">Ver Todos los Cursos</a>
                <a href="/jdeconomy/ai-work/" class="btn-secondary">Crear Página con IA</a>
            </div>
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-number">8</span>
                    <span class="stat-label">Cursos Disponibles</span>
                </div>
                <div class="stat">
                    <span class="stat-number">2</span>
                    <span class="stat-label">Áreas de Estudio</span>
                </div>
                <div class="stat">
                    <span class="stat-number">100%</span>
                    <span class="stat-label">Online y Certificado</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Cursos Macroeconomía -->
    <section class="courses-section" id="macroeconomia">
        <div class="section-header">
            <h2 class="section-title">Macroeconomía</h2>
            <p class="section-subtitle">Comprende la economía a gran escala y las políticas que la rigen</p>
            <a href="/jdeconomy/macroeconomia/" class="section-link">Ver todos</a>
        </div>
        <div class="courses-grid">
            <?php foreach ($macroCourses as $course): ?>
                <article class="course-card">
                    <div class="course-badge-macro">Macro</div>
                    <div class="course-content">
                        <h3><?= htmlspecialchars($course['title']) ?></h3>
                        <p><?= htmlspecialchars($course['subtitle']) ?></p>
                        <div class="course-meta">
                            <span><?= htmlspecialchars($course['duration']) ?></span>
                            <span><?= htmlspecialchars($course['level']) ?></span>
                            <span>$<?= number_format($course['price'], 0) ?></span>
                        </div>
                        <a href="/jdeconomy/macroeconomia/<?= htmlspecialchars($course['slug']) ?>/" class="btn-course">Ver Curso</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Cursos Ofimática -->
    <section class="courses-section ofimatica-section" id="ofimatica">
        <div class="section-header">
            <h2 class="section-title">Ofimática</h2>
            <p class="section-subtitle">Domina las herramientas esenciales de oficina empresarial</p>
            <a href="/jdeconomy/ofimatica/" class="section-link">Ver todos</a>
        </div>
        <div class="courses-grid">
            <?php foreach ($ofimaticaCourses as $course): ?>
                <article class="course-card">
                    <div class="course-badge-ofimatica">Ofimática</div>
                    <div class="course-content">
                        <h3><?= htmlspecialchars($course['title']) ?></h3>
                        <p><?= htmlspecialchars($course['subtitle']) ?></p>
                        <div class="course-meta">
                            <span><?= htmlspecialchars($course['duration']) ?></span>
                            <span><?= htmlspecialchars($course['level']) ?></span>
                            <span>$<?= number_format($course['price'], 0) ?></span>
                        </div>
                        <a href="/jdeconomy/ofimatica/<?= htmlspecialchars($course['slug']) ?>/" class="btn-course">Ver Curso</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>¿Listo para empezar?</h2>
            <p>Únete a cientos de estudiantes que ya están transformando sus carreras con JDEconomy Academia.</p>
            <a href="/jdeconomy/contacto/" class="btn-primary">Contáctanos Hoy</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <div class="logo-mark">JD</div>
                <div class="logo-text">JDEconomy Academia</div>
            </div>
            <p class="footer-text">Formación profesional en Macroeconomía y Ofimática © 2026 JDEconomy</p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook">F</a>
                <a href="#" aria-label="Twitter">T</a>
                <a href="#" aria-label="Instagram">I</a>
                <a href="#" aria-label="LinkedIn">L</a>
            </div>
        </div>
    </footer>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>
    <div class="mobile-menu" id="mobile-menu">
        <button class="mobile-close" id="mobile-close" aria-label="Cerrar">×</button>
        <nav class="mobile-nav">
            <?php foreach ($menuItems as $item): ?>
                <?php if ($item['parent_id'] === null): ?>
                    <?php
                    $children = array_filter($menuItems, fn($m) => $m['parent_id'] == $item['id']);
                    $hasChildren = !empty($children);
                    ?>
                    <div class="mobile-menu-item">
                        <a href="<?= htmlspecialchars($item['url']) ?>" class="mobile-link<?= $hasChildren ? ' mobile-has-children' : '' ?>">
                            <?= htmlspecialchars($item['name']) ?>
                            <?php if ($hasChildren): ?><span class="mobile-arrow">▾</span><?php endif; ?>
                        </a>
                        <?php if ($hasChildren): ?>
                            <div class="mobile-dropdown">
                                <?php foreach ($children as $child): ?>
                                    <a href="<?= htmlspecialchars($child['url']) ?>" class="mobile-dropdown-link">
                                        <?= htmlspecialchars($child['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>

    <script src="/jdeconomy/assets/js/app.js"></script>
</body>
</html>