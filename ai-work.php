<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
header('Content-Type: text/html; charset=utf-8');

$menus = [];
try {
    $mainMenus = getMenuItems($pdo);
    foreach ($mainMenus as $menu) {
        $menus[] = [
            'id' => $menu['id'],
            'name' => $menu['name'],
            'slug' => $menu['slug'],
            'url' => $menu['url'],
            'icon' => $menu['icon'],
            'type' => 'main',
            'children' => []
        ];

        $children = array_filter($menuItems ?? [], fn($m) => $m['parent_id'] == $menu['id']);
        foreach ($children as $child) {
            $menus[] = [
                'id' => $child['id'],
                'name' => $child['name'],
                'slug' => $child['slug'],
                'url' => $child['url'],
                'icon' => $child['icon'],
                'type' => 'sub',
                'parent_id' => $child['parent_id'],
                'parent_name' => $menu['name'],
                'children' => []
            ];
        }
    }
} catch (Exception $e) {
    $menus = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JDEconomy AI Work | Generador de Páginas con IA</title>
    <link rel="stylesheet" href="/jdeconomy/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="icon-defs">
        <svg id="icon-home" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        <svg id="icon-robot" viewBox="0 0 24 24"><path d="M12 22s8-4 8-4V5l-8-3-8 3v13s8 4 8 4zm0-21c2.2 0 4 1.8 4 4s-1.8 4-4 4-4-1.8-4-4 1.8-4 4-4zm0 14c3.3 0 6-2.7 6-6s-2.7-6-6-6-6 2.7-6 6 2.7 6 6 6zm0-11c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm0 8c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zM7.5 4a.5.5 0 0 1-.5.5v12a.5.5 0 0 1-1 0V4.5a.5.5 0 0 1 .5-.5zM17 4a.5.5 0 0 1-.5.5v12a.5.5 0 0 1-1 0V4.5a.5.5 0 0 1 .5-.5z"/></svg>
        <svg id="icon-architecture" viewBox="0 0 24 24"><path d="M3 21h18v-6H3v6zM3 2v18h18V2H3z"/></svg>
        <svg id="icon-construct" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        <svg id="icon-review" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
        <svg id="icon-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        <svg id="icon-check" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        <svg id="icon-arrow-right" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        <svg id="icon-close" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        <svg id="icon-sun" viewBox="0 0 24 24"><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2zm-1-12c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zM11 20H2c-.55 0-1 .45-1 1s.45 1 1 1h9c.55 0 1-.45 1-1s-.45-1-1-1zM20.5 10c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5S18.17 13 19 13s1.5-.67 1.5-1.5zM12 2c-3.86 0-7 3.14-7 7s3.14 7 7 7 7-3.14 7-7-3.14-7-7-7z"/></svg>
        <svg id="icon-moon" viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-3.3 0-6-2.7-6-6 0-1.21.32-2.33.88-3.28 1.17-1.16 2.62-1.84 4.2-1.98-.43-1.18-1.51-2-2.82-2zm-1 15c-3.3 0-6-2.7-6-6 0-2.09 1.23-3.9 3-4.76.34.28.54.7.54 1.16 0 1.66 1.34 3 3 3s3-1.34 3-3-1.34-3-3-3c-2.21 0-4 1.79-4 4 0 .57.19 1.09.46 1.54-.92 1.25-1.46 2.73-1.46 4.24 0 3.31 2.69 6 6 6zm11-5c-.47 0-.93-.09-1.37-.25-.14-.16-.21-.39-.21-.63 0-.6.45-1.08 1.05-1.18 1.36-.22 2.44-1.18 2.93-2.58.49-1.41.49-2.95 0-4.36-.49-1.4-1.57-2.36-2.93-2.58-.6-.1-1.05.58-1.05 1.18 0 .24-.07.47-.21.63-.44.16-.9.25-1.37.25-2.2 0-4-1.8-4-4 0-1.1.46-2.08 1.19-2.78.8-.79 1.8-1.26 2.81-1.26 2.2 0 4 1.8 4 4s-1.8 4-4 4z"/></svg>
    </div>

    <header class="header">
        <div class="header-inner">
            <div class="logo">
                <div class="logo-mark">JD</div>
                <div class="logo-text">JDEconomy AI Work</div>
            </div>
            <nav class="nav-menu">
                <a href="/jdeconomy/" class="nav-link">
                    <span class="icon icon-sm"><svg><use href="#icon-home"></use></svg></span>
                    Volver a Academia
                </a>
                <a href="#" class="nav-link active">
                    <span class="icon icon-sm"><svg><use href="#icon-robot"></use></svg></span>
                    AI Work
                </a>
            </nav>
            <nav class="nav-actions">
                <button class="theme-toggle" id="theme-toggle" aria-label="Cambiar tema">
                    <span class="theme-icon">
                        <svg id="theme-sun" width="18" height="18"><use href="#icon-sun"></use></svg>
                        <svg id="theme-moon" width="18" height="18" style="display:none"><use href="#icon-moon"></use></svg>
                    </span>
                </button>
                <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Menú">
                    <span></span><span></span><span></span>
                </button>
            </nav>
        </div>
    </header>

    <div class="mobile-menu" id="mobile-menu">
        <button class="mobile-close" id="mobile-close" aria-label="Cerrar menú">
            <svg width="20" height="20"><use href="#icon-close"></use></svg>
        </button>
        <nav class="mobile-nav">
            <a href="/jdeconomy/" class="mobile-link">Volver a Academia</a>
            <a href="#" class="mobile-link active">AI Work</a>
        </nav>
    </div>

    <main class="ai-work-container">
        <header class="page-header">
            <h1>AI Work</h1>
            <p>Generador de páginas con agentes de Cloudflare AI Workers</p>
        </header>

        <!-- Formulario Principal -->
        <div class="form-card">
            <div class="form-section">
                <h2><span class="icon icon-sm"><svg><use href="#icon-architecture"></use></svg></span> Configuración de Página</h2>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="page-slug">Slug de la Página <span style="color: var(--color-primary);">*</span></label>
                        <input type="text" id="page-slug" placeholder="ej: consultoria" maxlength="50">
                        <small>La URL será: /jdeconomy/[menu]/consultoria/</small>
                    </div>
                    <div class="form-field">
                        <label for="menu-parent">Pertenece al Menú <span style="color: var(--color-primary);">*</span></label>
                        <select id="menu-parent">
                            <option value="sidebar">Sidebar Superior (aparece directamente arriba)</option>
                            <?php foreach ($menus as $m): ?>
                                <option value="<?= htmlspecialchars($m['slug']) ?>" <?= $m['type'] === 'sub' ? 'disabled' : '' ?>>
                                    <?php if ($m['type'] === 'sub'): ?>─ <?php endif; ?>
                                    <?= htmlspecialchars($m['name']) ?>
                                    <?php if ($m['type'] === 'main'): ?> (<?= count(array_filter($menus, fn($x) => isset($x['parent_id']) && $x['parent_id'] == $m['id'])) ?> submenú<?= count(array_filter($menus, fn($x) => isset($x['parent_id']) && $x['parent_id'] == $m['id'])) > 1 ? 's' : '' ?>><?php endif; ?>
                                </option>
                                <?php if ($m['type'] === 'main'): ?>
                                    <?php foreach ($menus as $child): ?>
                                        <?php if ($child['type'] === 'sub' && $child['parent_id'] == $m['id']): ?>
                                            <option value="<?= htmlspecialchars($child['slug']) ?>">
                                                └─ <?= htmlspecialchars($child['name']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <option value="personalizado">Menú Personalizado</option>
                        </select>
                    </div>
                    <div class="form-field" id="menu-custom-field" style="display: none;">
                        <label for="menu-custom-name">Nombre del ítem personalizado</label>
                        <input type="text" id="menu-custom-name" placeholder="Ej: Mis Servicios">
                        <small>Nombre que aparecerá en el menú superior</small>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2><span class="icon icon-sm"><svg><use href="#icon-robot"></use></svg></span> Prompt de Generación</h2>
                <div class="form-field form-field-full">
                    <label for="prompt">Describe la página que deseas crear</label>
                    <textarea id="prompt" rows="10" placeholder="Ejemplo: Crea una página de servicios de consultoría financiera con hero section, 3 features, testimonios y CTA final. Usa colores profesionales y diseño moderno."></textarea>
                </div>
                <div class="templates-grid">
                    <div class="template-card" onclick="loadTemplate('consultoria')" data-template="consultoria">
                        <h4>Consultoría Financiera</h4>
                        <p>Página completa con hero, features, testimonios y CTA para consultoría financiera.</p>
                        <div class="template-tags">
                            <span class="template-tag">Hero</span>
                            <span class="template-tag">Features</span>
                            <span class="template-tag">Testimonios</span>
                            <span class="template-tag">CTA</span>
                        </div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('academia')" data-template="academia">
                        <h4>Academia Educativa</h4>
                        <p>Página para cursos online con listado de programas, precios y formulario de inscripción.</p>
                        <div class="template-tags">
                            <span class="template-tag">Cursos</span>
                            <span class="template-tag">Precios</span>
                            <span class="template-tag">Inscripción</span>
                        </div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('corporativo')" data-template="corporativo">
                        <h4>Sitio Corporativo</h4>
                        <p>Página institucional con nosotros, servicios, equipo y contacto.</p>
                        <div class="template-tags">
                            <span class="template-tag">Institucional</span>
                            <span class="template-tag">Servicios</span>
                            <span class="template-tag">Equipo</span>
                        </div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('ecommerce')" data-template="ecommerce">
                        <h4>E-commerce</h4>
                        <p>Tienda online con catálogo de productos, carrito y checkout.</p>
                        <div class="template-tags">
                            <span class="template-tag">Productos</span>
                            <span class="template-tag">Carrito</span>
                            <span class="template-tag">Pago</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón de Generación -->
        <button class="btn-generate" id="btn-generate">
            <span>Generar Página con IA</span>
        </button>

        <!-- Pipeline de Agentes -->
        <div class="agent-pipeline" id="agent-pipeline" style="display: none;">
            <div class="pipeline-header">
                <h3><span class="icon icon-sm"><svg><use href="#icon-review"></use></svg></span> Proceso de Generación</h3>
                <div class="progress-container">
                    <div class="progress-bar" id="progress-bar"></div>
                    <span class="progress-text" id="progress-text">0%</span>
                </div>
                <div class="compact-prompt" id="compact-prompt" style="display: none;">
                    <span class="icon icon-sm"><svg><use href="#icon-robot"></use></svg></span>
                    <span id="compact-prompt-text"></span>
                </div>
            </div>
            <div class="pipeline-steps">
                <div class="pipeline-step" id="step-1">
                    <div class="step-number">1</div>
                    <div class="step-label">Arquitecto</div>
                </div>
                <div class="pipeline-step" id="step-2">
                    <div class="step-number">2</div>
                    <div class="step-label">Constructor</div>
                </div>
                <div class="pipeline-step" id="step-3">
                    <div class="step-number">3</div>
                    <div class="step-label">Supervisor</div>
                </div>
            </div>
            <div class="step-content" id="content-1">
                <h4>Agente 1 - Arquitecto de Página</h4>
                <div class="agent-thinking">
                    <span class="thinking-label">Pensando:</span>
                    <p id="msg-1">Analizando el prompt y definiendo la estructura semántica...</p>
                </div>
                <div class="agent-output" id="output-1" style="display: none;">
                    <span class="output-label">Respuesta:</span>
                    <pre id="raw-1"></pre>
                </div>
            </div>
            <div class="step-content" id="content-2">
                <h4>Agente 2 - Constructor de Archivos</h4>
                <div class="agent-thinking">
                    <span class="thinking-label">Pensando:</span>
                    <p id="msg-2">Generando archivos HTML, CSS y JS... Publicando en /[menu]/[slug]/</p>
                </div>
                <div class="agent-output" id="output-2" style="display: none;">
                    <span class="output-label">Respuesta:</span>
                    <pre id="raw-2"></pre>
                </div>
            </div>
            <div class="step-content" id="content-3">
                <h4>Agente 3 - Supervisor / Mejorador</h4>
                <div class="agent-thinking">
                    <span class="thinking-label">Pensando:</span>
                    <p id="msg-3">Revisando calidad, accesibilidad y rendimiento...</p>
                </div>
                <div class="agent-output" id="output-3" style="display: none;">
                    <span class="output-label">Respuesta:</span>
                    <pre id="raw-3"></pre>
                </div>
            </div>
        </div>

        <!-- Vista Previa -->
        <div class="preview-section" id="preview-section" style="display: none;">
            <div class="preview-header">
                <h3><span class="icon icon-sm"><svg><use href="#icon-play"></use></svg></span> Vista Previa</h3>
                <div class="preview-actions">
                    <button class="btn-outline" id="btn-copy-url">Copiar URL</button>
                    <a class="btn-secondary" id="btn-open-page" target="_blank">Abrir en nueva pestaña</a>
                </div>
            </div>
            <div class="preview-frame-container">
                <iframe class="preview-frame" id="preview-frame" src="" title="Vista previa de la página generada"></iframe>
            </div>
        </div>

        <!-- Alertas -->
        <div id="alert-container"></div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">JDEconomy AI Work</div>
            <div class="footer-text">Generador de páginas con agentes de Cloudflare AI Workers © 2026</div>
        </div>
    </footer>

    <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>

    <script src="/jdeconomy/assets/js/app.js"></script>
</body>
</html>