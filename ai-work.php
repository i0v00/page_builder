<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
header('Content-Type: text/html; charset=utf-8');

$menus = [];
try {
    $allMenus = getAllMenuItems($pdo);
    $mainMenus = array_filter($allMenus, fn($m) => $m['parent_id'] === null);
    foreach ($mainMenus as $menu) {
        $children = array_values(array_filter($allMenus, fn($m) => $m['parent_id'] == $menu['id']));
        $menus[] = [
            'id' => $menu['id'],
            'name' => $menu['name'],
            'slug' => $menu['slug'],
            'url' => $menu['url'],
            'icon' => $menu['icon'],
            'type' => 'main',
            'children' => $children
        ];
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
    <title>JDEconomy AI Work | Generador Autónomo de Páginas con IA</title>
    <link rel="stylesheet" href="/jdeconomy/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Definición de Iconos SVG -->
    <svg class="icon-defs" style="display:none;" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <symbol id="icon-home" viewBox="0 0 24 24"><path fill="currentColor" d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></symbol>
            <symbol id="icon-robot" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.38-1 1.72V7h4a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-8a3 3 0 0 1 3-3h4V5.72c-.6-.34-1-.98-1-1.72a2 2 0 0 1 2-2M7.5 11.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3m9 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3M8 17h8v1.5H8z"/></symbol>
            <symbol id="icon-architecture" viewBox="0 0 24 24"><path fill="currentColor" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2m0 16H5V7h14zm-2-2H7v-2h10zm0-4H7v-2h10z"/></symbol>
            <symbol id="icon-construct" viewBox="0 0 24 24"><path fill="currentColor" d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6z"/></symbol>
            <symbol id="icon-review" viewBox="0 0 24 24"><path fill="currentColor" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9z"/></symbol>
            <symbol id="icon-play" viewBox="0 0 24 24"><path fill="currentColor" d="M8 5v14l11-7z"/></symbol>
            <symbol id="icon-check" viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></symbol>
            <symbol id="icon-arrow-right" viewBox="0 0 24 24"><path fill="currentColor" d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></symbol>
            <symbol id="icon-close" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></symbol>
            <symbol id="icon-sun" viewBox="0 0 24 24"><path fill="currentColor" d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2zm-9 7h2c.55 0 1-.45 1-1s-.45-1-1-1h-2zm0-18h2c.55 0 1-.45 1-1s-.45-1-1-1h-2zm7.07 3.51l1.41-1.41c.39-.39.39-1.02 0-1.41s-1.02-.39-1.41 0l-1.41 1.41c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0zM4.93 17.66l1.41-1.41c.39-.39.39-1.02 0-1.41s-1.02-.39-1.41 0l-1.41 1.41c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0zm14.14 0c-.39-.39-1.02-.39-1.41 0s-.39 1.02 0 1.41l1.41 1.41c.39.39 1.02.39 1.41 0s.39-1.02 0-1.41zm-14.14-14.14c-.39-.39-1.02-.39-1.41 0s-.39 1.02 0 1.41l1.41 1.41c.39.39 1.02.39 1.41 0s.39-1.02 0-1.41z"/></symbol>
            <symbol id="icon-moon" viewBox="0 0 24 24"><path fill="currentColor" d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-3.3 0-6-2.7-6-6 0-1.21.32-2.33.88-3.28 1.17-1.16 2.62-1.84 4.2-1.98-.43-1.18-1.51-2-2.82-2z"/></symbol>
        </defs>
    </svg>

    <!-- Header Principal -->
    <header class="header">
        <div class="header-inner">
            <a href="/jdeconomy/" class="logo">
                <div class="logo-mark">JD</div>
                <div class="logo-text">JDEconomy <span style="font-weight:400; font-size:0.85rem; opacity:0.7;">AI Work</span></div>
            </a>
            <nav class="nav-menu">
                <a href="/jdeconomy/" class="nav-link">
                    <span class="icon icon-sm"><svg><use href="#icon-home"></use></svg></span>
                    Academia
                </a>
                <a href="/jdeconomy/macroeconomia/" class="nav-link">
                    Macroeconomía
                </a>
                <a href="/jdeconomy/ofimatica/" class="nav-link">
                    Ofimática
                </a>
                <a href="/jdeconomy/ai-work/" class="nav-link active">
                    <span class="icon icon-sm"><svg><use href="#icon-robot"></use></svg></span>
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

    <div class="mobile-menu" id="mobile-menu">
        <button class="mobile-close" id="mobile-close" aria-label="Cerrar menú">
            <svg width="20" height="20"><use href="#icon-close"></use></svg>
        </button>
        <nav class="mobile-nav">
            <a href="/jdeconomy/" class="mobile-link">Academia</a>
            <a href="/jdeconomy/macroeconomia/" class="mobile-link">Macroeconomía</a>
            <a href="/jdeconomy/ofimatica/" class="mobile-link">Ofimática</a>
            <a href="/jdeconomy/ai-work/" class="mobile-link active">AI Work</a>
        </nav>
    </div>

    <main class="ai-work-container">
        <!-- Hero Header -->
        <header class="page-header">
            <div class="hero-badge">
                <span class="badge-dot"></span>
                <span>Pipeline Multi-Agente • Cloudflare Workers AI</span>
            </div>
            <h1>Generador Autónomo de Páginas Web</h1>
            <p>Crea páginas profesionales y responsivas con un equipo de 3 agentes de IA colaborativos: Arquitecto, Constructor y Supervisor de Calidad.</p>
        </header>

        <!-- Formulario Principal -->
        <div class="form-card">
            <!-- Sección 1: Configuración de Publicación -->
            <div class="form-section">
                <h2>
                    <span class="section-icon"><svg><use href="#icon-architecture"></use></svg></span>
                    1. Configuración de Destino y Ruta
                </h2>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="page-slug">Slug de la Página <span class="required">*</span></label>
                        <input type="text" id="page-slug" placeholder="ej: consultoria-financiera" maxlength="50" autocomplete="off" spellcheck="false">
                        <small>Solo letras minúsculas, números y guiones.</small>
                    </div>

                    <div class="form-field">
                        <label for="menu-parent">Pertenece al Menú / Categoría <span class="required">*</span></label>
                        <select id="menu-parent">
                            <option value="sidebar">📌 Sidebar Superior (Acceso directo /[slug]/)</option>
                            <optgroup label="Menús Principales y Submenús de la Academia">
                            <?php foreach ($menus as $m): ?>
                                <option value="<?= htmlspecialchars($m['slug']) ?>">
                                    📂 <?= htmlspecialchars($m['name']) ?> (/<?= htmlspecialchars($m['slug']) ?>/)
                                </option>
                                <?php foreach ($m['children'] as $child): ?>
                                    <option value="<?= htmlspecialchars($child['slug']) ?>">
                                        &nbsp;&nbsp;&nbsp;&nbsp;↳ 📄 <?= htmlspecialchars($child['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            </optgroup>
                            <option value="personalizado">✨ Menú Personalizado...</option>
                        </select>
                    </div>

                    <div class="form-field" id="menu-custom-field" style="display: none;">
                        <label for="menu-custom-name">Nombre del Menú Personalizado <span class="required">*</span></label>
                        <input type="text" id="menu-custom-name" placeholder="Ej: Especialidades">
                        <small>Nombre que identificará a la sección.</small>
                    </div>
                </div>

                <!-- Live URL Preview -->
                <div class="url-live-preview">
                    <span class="url-preview-tag">URL FINAL DESTINO:</span>
                    <code class="url-preview-code" id="live-url-display">http://localhost/jdeconomy/sidebar/pagina/</code>
                </div>
            </div>

            <!-- Sección 2: Plantillas y Prompt -->
            <div class="form-section">
                <h2>
                    <span class="section-icon"><svg><use href="#icon-robot"></use></svg></span>
                    2. Prompt de Generación y Plantillas
                </h2>

                <div class="form-field form-field-full">
                    <div class="field-header">
                        <label for="prompt">Describe la página web en detalle <span class="required">*</span></label>
                        <span class="prompt-hint">Puedes escribir tus requerimientos o elegir una plantilla abajo</span>
                    </div>
                    <textarea id="prompt" rows="8" placeholder="Ejemplo: Crea una página profesional de servicios de consultoría financiera. Incluye una sección hero impactante con título, subtítulo y CTA. Una sección de descripción sobre los servicios ofrecidos. Tres features principales: asesoría tributaria, planificación financiera y auditoría. Una sección de testimonios con 3 clientes satisfechos. Un CTA final con botón de contacto y footer con información..."></textarea>
                </div>

                <!-- Plantillas Rápidas -->
                <div class="templates-header">
                    <span>O elige una plantilla prediseñada para autocompletar:</span>
                </div>
                <div class="templates-grid">
                    <div class="template-card" data-template="consultoria">
                        <div class="template-card-top">
                            <span class="template-icon">💼</span>
                            <h4>Consultoría Financiera</h4>
                        </div>
                        <p>Hero impactante, 3 servicios con tarjetas, testimonios verificados y formulario CTA para consultoría.</p>
                        <div class="template-tags">
                            <span class="template-tag">Hero</span>
                            <span class="template-tag">Servicios</span>
                            <span class="template-tag">Testimonios</span>
                            <span class="template-tag">CTA</span>
                        </div>
                    </div>

                    <div class="template-card" data-template="academia">
                        <div class="template-card-top">
                            <span class="template-icon">🎓</span>
                            <h4>Academia Educativa</h4>
                        </div>
                        <p>Catálogo de cursos online, temarios, tabla de precios, opiniones de alumnos y formulario de matrícula.</p>
                        <div class="template-tags">
                            <span class="template-tag">Cursos</span>
                            <span class="template-tag">Precios</span>
                            <span class="template-tag">Inscripción</span>
                        </div>
                    </div>

                    <div class="template-card" data-template="corporativo">
                        <div class="template-card-top">
                            <span class="template-icon">🏢</span>
                            <h4>Sitio Corporativo</h4>
                        </div>
                        <p>Página institucional con misión/visión, valores, equipo directivo, servicios y formulario de contacto.</p>
                        <div class="template-tags">
                            <span class="template-tag">Nosotros</span>
                            <span class="template-tag">Valores</span>
                            <span class="template-tag">Equipo</span>
                        </div>
                    </div>

                    <div class="template-card" data-template="ecommerce">
                        <div class="template-card-top">
                            <span class="template-icon">🛒</span>
                            <h4>E-commerce / Catálogo</h4>
                        </div>
                        <p>Vitrina de productos con filtros por categoría, tarjetas de precio, badges de oferta y llamadas a la acción.</p>
                        <div class="template-tags">
                            <span class="template-tag">Productos</span>
                            <span class="template-tag">Filtros</span>
                            <span class="template-tag">Precios</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón de Generación Principal -->
        <div class="generate-action-bar">
            <button class="btn-generate" id="btn-generate">
                <span class="btn-generate-icon">⚡</span>
                <span class="btn-generate-text">Generar Página con Agentes IA</span>
            </button>
        </div>

        <!-- Pipeline de Agentes Colaborativos -->
        <div class="agent-pipeline" id="agent-pipeline" style="display: none;">
            <!-- Header del Pipeline -->
            <div class="pipeline-header-card">
                <div class="pipeline-header-top">
                    <div class="pipeline-title-group">
                        <div class="pipeline-status-indicator pulse" id="pipeline-status-dot"></div>
                        <div>
                            <h3 class="pipeline-title">Proceso de Generación Autónomo</h3>
                            <p class="pipeline-subtitle" id="pipeline-subtitle">Inicializando agentes de Cloudflare AI Workers...</p>
                        </div>
                    </div>
                    <div class="pipeline-badge" id="pipeline-badge">En Ejecución</div>
                </div>

                <!-- Barra de Progreso con Track y Relleno -->
                <div class="pipeline-progress-wrapper">
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill" id="progress-bar" style="width: 0%;"></div>
                    </div>
                    <div class="progress-meta">
                        <span class="progress-phase" id="progress-phase">Fase 1 de 3: Arquitectura</span>
                        <span class="progress-text" id="progress-text">0%</span>
                    </div>
                </div>

                <!-- Prompt Compacto -->
                <div class="compact-prompt" id="compact-prompt" style="display: none;">
                    <span class="compact-prompt-icon">🤖</span>
                    <div class="compact-prompt-info">
                        <span class="compact-prompt-label">Prompt Activo:</span>
                        <span class="compact-prompt-text" id="compact-prompt-text"></span>
                    </div>
                </div>
            </div>

            <!-- Stepper Visual de 3 Fases con Conectores -->
            <div class="pipeline-steps-container">
                <div class="pipeline-step-item" id="step-1">
                    <div class="step-node">
                        <div class="step-icon-wrap">
                            <span class="step-num">1</span>
                            <span class="step-check">✔</span>
                        </div>
                    </div>
                    <div class="step-meta">
                        <span class="step-name">Arquitecto</span>
                        <span class="step-status" id="step-1-status">En espera</span>
                    </div>
                </div>
                <div class="step-connector" id="connector-1"></div>
                <div class="pipeline-step-item" id="step-2">
                    <div class="step-node">
                        <div class="step-icon-wrap">
                            <span class="step-num">2</span>
                            <span class="step-check">✔</span>
                        </div>
                    </div>
                    <div class="step-meta">
                        <span class="step-name">Constructor</span>
                        <span class="step-status" id="step-2-status">En espera</span>
                    </div>
                </div>
                <div class="step-connector" id="connector-2"></div>
                <div class="pipeline-step-item" id="step-3">
                    <div class="step-node">
                        <div class="step-icon-wrap">
                            <span class="step-num">3</span>
                            <span class="step-check">✔</span>
                        </div>
                    </div>
                    <div class="step-meta">
                        <span class="step-name">Supervisor</span>
                        <span class="step-status" id="step-3-status">En espera</span>
                    </div>
                </div>
            </div>

            <!-- Tarjetas Interactivas de los 3 Agentes -->
            <div class="agent-cards-container">
                <!-- Agente 1: Arquitecto -->
                <div class="agent-card" id="content-1">
                    <div class="agent-card-header">
                        <div class="agent-identity">
                            <div class="agent-avatar architect-avatar">📐</div>
                            <div>
                                <h4>Agente 1 — Arquitecto de Página</h4>
                                <span class="agent-model-tag">Llama-3.1-70B • Planificación y Estructura Semántica</span>
                            </div>
                        </div>
                        <span class="agent-pill" id="pill-1">En espera</span>
                    </div>
                    <div class="agent-card-body">
                        <div class="agent-status-box" id="status-box-1">
                            <div class="agent-spinner" id="spinner-1"></div>
                            <p id="msg-1">Analizando el prompt y definiendo la jerarquía y estructura de secciones...</p>
                        </div>
                        <div class="agent-output" id="output-1" style="display: none;">
                            <div class="output-header">
                                <span class="output-label">✔ Estructura Arquitectónica Aprobada</span>
                                <button type="button" class="btn-toggle-raw" onclick="toggleRaw('raw-1')">Ver JSON</button>
                            </div>
                            <div class="output-summary" id="summary-1"></div>
                            <pre id="raw-1" style="display: none;"></pre>
                        </div>
                    </div>
                </div>

                <!-- Agente 2: Constructor -->
                <div class="agent-card" id="content-2">
                    <div class="agent-card-header">
                        <div class="agent-identity">
                            <div class="agent-avatar builder-avatar">⚡</div>
                            <div>
                                <h4>Agente 2 — Constructor de Archivos</h4>
                                <span class="agent-model-tag">Qwen-2.5-Coder-32B • Generación HTML5, CSS3 y JS</span>
                            </div>
                        </div>
                        <span class="agent-pill" id="pill-2">En espera</span>
                    </div>
                    <div class="agent-card-body">
                        <div class="agent-status-box" id="status-box-2">
                            <div class="agent-spinner" id="spinner-2"></div>
                            <p id="msg-2">Generando código fuente limpio, estilos Guindo responsivos y scripts interactivos...</p>
                        </div>
                        <div class="agent-output" id="output-2" style="display: none;">
                            <div class="output-header">
                                <span class="output-label">✔ Código Generado y Publicado en Servidor</span>
                                <button type="button" class="btn-toggle-raw" onclick="toggleRaw('raw-2')">Ver Archivos</button>
                            </div>
                            <div class="output-summary" id="summary-2"></div>
                            <pre id="raw-2" style="display: none;"></pre>
                        </div>
                    </div>
                </div>

                <!-- Agente 3: Supervisor -->
                <div class="agent-card" id="content-3">
                    <div class="agent-card-header">
                        <div class="agent-identity">
                            <div class="agent-avatar supervisor-avatar">🛡️</div>
                            <div>
                                <h4>Agente 3 — Supervisor de Calidad y UX</h4>
                                <span class="agent-model-tag">Llama-3.1-70B • Accesibilidad WCAG, Rendimiento y Polish</span>
                            </div>
                        </div>
                        <span class="agent-pill" id="pill-3">En espera</span>
                    </div>
                    <div class="agent-card-body">
                        <div class="agent-status-box" id="status-box-3">
                            <div class="agent-spinner" id="spinner-3"></div>
                            <p id="msg-3">Auditando contraste de color, coherencia visual, espaciado y soporte dark mode...</p>
                        </div>
                        <div class="agent-output" id="output-3" style="display: none;">
                            <div class="output-header">
                                <span class="output-label">✔ Auditoría y Mejoras de Calidad Aplicadas</span>
                                <button type="button" class="btn-toggle-raw" onclick="toggleRaw('raw-3')">Ver Detalles</button>
                            </div>
                            <div class="output-summary" id="summary-3"></div>
                            <pre id="raw-3" style="display: none;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista Previa Profesional Tipo Browser Mockup -->
        <div class="preview-section" id="preview-section" style="display: none;">
            <div class="browser-mockup">
                <div class="browser-header">
                    <div class="browser-dots">
                        <span class="dot dot-red"></span>
                        <span class="dot dot-yellow"></span>
                        <span class="dot dot-green"></span>
                    </div>
                    <div class="browser-address-bar">
                        <span class="browser-ssl-icon">🔒</span>
                        <span class="browser-url-text" id="browser-url-text">http://localhost/jdeconomy/...</span>
                    </div>
                    <div class="browser-viewport-controls">
                        <button class="viewport-btn active" data-viewport="desktop" title="Escritorio (100%)">💻</button>
                        <button class="viewport-btn" data-viewport="tablet" title="Tablet (768px)">📱</button>
                        <button class="viewport-btn" data-viewport="mobile" title="Móvil (375px)">📲</button>
                    </div>
                    <div class="browser-actions">
                        <button type="button" class="btn-preview-action" id="btn-copy-url" title="Copiar URL al portapapeles">📋 Copiar URL</button>
                        <button type="button" class="btn-preview-action" id="btn-reload-preview" title="Recargar vista previa">🔄</button>
                        <a class="btn-preview-open" id="btn-open-page" target="_blank" title="Abrir página en nueva pestaña">Abrir en pestaña ↗</a>
                    </div>
                </div>
                <div class="browser-viewport-wrapper">
                    <div class="preview-frame-container" id="preview-container">
                        <div class="preview-loading-overlay" id="preview-loading" style="display: none;">
                            <div class="loading-spinner"></div>
                            <span>Cargando página generada...</span>
                        </div>
                        <iframe class="preview-frame" id="preview-frame" src="" title="Vista previa de la página generada"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenedor de Alertas Flotantes -->
        <div id="alert-container"></div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-logo">JDEconomy Academia</div>
            <div class="footer-text">Generador autónomo de páginas web con Cloudflare Workers AI © 2026</div>
        </div>
    </footer>

    <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>

    <script src="/jdeconomy/assets/js/app.js"></script>
</body>
</html>
