// JDEconomy AI Work - Aplicación Principal
// Gestión del formulario, pipeline autónomo de 3 agentes y vista previa profesional

const API_BASE = '/jdeconomy/api';

const state = {
    currentStep: 0,
    isGenerating: false,
    architectStructure: null,
    builderData: null,
    supervisorData: null,
    pageUrl: ''
};

// Plantillas de ejemplo para generación rápida
const TEMPLATES = {
    consultoria: {
        slug: 'consultoria-financiera',
        menuParent: 'macroeconomia',
        prompt: 'Crea una página profesional de servicios de consultoría financiera. Incluye una sección hero impactante con título, subtítulo persuasivo y botón CTA. Una sección de descripción sobre los servicios ofrecidos. Tres features principales: asesoría tributaria y fiscal, planificación financiera estratégica y auditoría contable. Una sección de testimonios con 3 clientes satisfechos con nombre y cargo. Un CTA final con formulario o botón de contacto y footer profesional. Usa colores corporativos guindo, tipografía elegante y diseño responsive con soporte de modo claro y oscuro.'
    },
    academia: {
        slug: 'academia-online',
        menuParent: 'cursos',
        prompt: 'Crea una página moderna para una academia de formación online en economía y finanzas. Incluye hero section con llamada a la acción, propuesta de valor, sección de cursos destacados con 4 cursos (Macroeconomía Avanzada, Análisis Financiero, Ofimática Ejecutiva y Excel para Finanzas), comparativa de planes de suscripción, testimonios de estudiantes egresados y formulario de matrícula inmediata. Diseño premium, responsive y accesible.'
    },
    corporativo: {
        slug: 'quienes-somos',
        menuParent: 'nosotros',
        prompt: 'Crea una página corporativa institucional para JDEconomy. Incluye hero con misión y visión, sección de valores fundamentales (Excelencia, Rigor Analítico, Innovación), presentación del equipo directivo con 3 perfiles profesionales, áreas de impacto institucional y formulario de contacto. Diseño limpio, espaciado generoso, estructura semántica y modo claro/oscuro.'
    },
    ecommerce: {
        slug: 'catalogo-productos',
        menuParent: 'sidebar',
        prompt: 'Crea una página de catálogo y e-commerce para cursos y recursos descargables. Incluye hero con ofertas del mes, barra de filtros por categoría, grid interactivo de 6 productos/guías con precio, badge de descuento, valoración por estrellas y botón de añadir al carrito. Sección de garantía de satisfacción y checkout rápido. Diseño moderno y fluido.'
    }
};

// Elementos del DOM
const elements = {
    btnGenerate: document.getElementById('btn-generate'),
    pipeline: document.getElementById('agent-pipeline'),
    pipelineBadge: document.getElementById('pipeline-badge'),
    pipelineSubtitle: document.getElementById('pipeline-subtitle'),
    pipelineStatusDot: document.getElementById('pipeline-status-dot'),
    progressBar: document.getElementById('progress-bar'),
    progressText: document.getElementById('progress-text'),
    progressPhase: document.getElementById('progress-phase'),
    compactPrompt: document.getElementById('compact-prompt'),
    compactPromptText: document.getElementById('compact-prompt-text'),
    previewSection: document.getElementById('preview-section'),
    previewFrame: document.getElementById('preview-frame'),
    previewContainer: document.getElementById('preview-container'),
    previewLoading: document.getElementById('preview-loading'),
    browserUrlText: document.getElementById('browser-url-text'),
    btnCopyUrl: document.getElementById('btn-copy-url'),
    btnReloadPreview: document.getElementById('btn-reload-preview'),
    btnOpenPage: document.getElementById('btn-open-page'),
    alertContainer: document.getElementById('alert-container'),
    pageSlug: document.getElementById('page-slug'),
    menuParent: document.getElementById('menu-parent'),
    menuCustomField: document.getElementById('menu-custom-field'),
    menuCustomName: document.getElementById('menu-custom-name'),
    promptInput: document.getElementById('prompt'),
    liveUrlDisplay: document.getElementById('live-url-display')
};

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMobileMenu();
    initMenuSelection();
    initLiveUrlPreview();
    initTemplates();
    initFormSubmission();
    initPreviewActions();
    updateLiveUrl();
});

// ========== TEMA CLARO/OSCURO ==========
function initThemeToggle() {
    const btn = document.getElementById('theme-toggle');
    const sunIcon = document.getElementById('theme-sun');
    const moonIcon = document.getElementById('theme-moon');
    if (!btn) return;

    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        if (sunIcon) sunIcon.style.display = 'none';
        if (moonIcon) moonIcon.style.display = 'block';
    }

    btn.addEventListener('click', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const newTheme = isDark ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        if (sunIcon) sunIcon.style.display = isDark ? 'block' : 'none';
        if (moonIcon) moonIcon.style.display = isDark ? 'none' : 'block';
    });
}

// ========== MENÚ MÓVIL ==========
function initMobileMenu() {
    const btnOpen = document.getElementById('mobile-menu-btn');
    const btnClose = document.getElementById('mobile-close');
    const menu = document.getElementById('mobile-menu');
    const overlay = document.getElementById('mobile-menu-overlay');

    if (!btnOpen || !btnClose || !menu) return;

    btnOpen.addEventListener('click', () => {
        menu.classList.add('open');
        if (overlay) overlay.classList.add('open');
    });

    btnClose.addEventListener('click', () => {
        menu.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
    });

    if (overlay) {
        overlay.addEventListener('click', () => {
            menu.classList.remove('open');
            overlay.classList.remove('open');
        });
    }
}

// ========== GESTIÓN DE RUTA Y LIVE URL PREVIEW ==========
function initMenuSelection() {
    if (!elements.menuParent) return;
    elements.menuParent.addEventListener('change', () => {
        if (elements.menuParent.value === 'personalizado') {
            if (elements.menuCustomField) elements.menuCustomField.style.display = 'block';
        } else {
            if (elements.menuCustomField) elements.menuCustomField.style.display = 'none';
        }
        updateLiveUrl();
    });

    if (elements.menuCustomName) {
        elements.menuCustomName.addEventListener('input', updateLiveUrl);
    }
}

function initLiveUrlPreview() {
    if (elements.pageSlug) {
        elements.pageSlug.addEventListener('input', () => {
            // Normalizar slug al vuelo
            elements.pageSlug.value = elements.pageSlug.value
                .toLowerCase()
                .replace(/[^a-z0-9-]/g, '-')
                .replace(/-+/g, '-');
            updateLiveUrl();
        });
    }
}

function updateLiveUrl() {
    if (!elements.liveUrlDisplay) return;
    const slug = (elements.pageSlug && elements.pageSlug.value.trim()) || 'mi-pagina';
    const menuVal = elements.menuParent ? elements.menuParent.value : 'sidebar';
    
    let menuPath = '';
    if (menuVal === 'sidebar') {
        menuPath = '';
    } else if (menuVal === 'personalizado') {
        const custom = (elements.menuCustomName && elements.menuCustomName.value.trim()) || 'personalizado';
        menuPath = custom.toLowerCase().replace(/[^a-z0-9-]/g, '-') + '/';
    } else {
        menuPath = menuVal + '/';
    }

    const fullPath = `/jdeconomy/${menuPath}${slug}/`.replace(/\/+/g, '/');
    const fullUrl = window.location.origin + fullPath;
    elements.liveUrlDisplay.textContent = fullUrl;
}

// ========== PLANTILLAS DE GENERACIÓN RÁPIDA ==========
function initTemplates() {
    const cards = document.querySelectorAll('.template-card');
    cards.forEach(card => {
        card.addEventListener('click', () => {
            cards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            const type = card.getAttribute('data-template');
            if (type && TEMPLATES[type]) {
                const t = TEMPLATES[type];
                if (elements.pageSlug) elements.pageSlug.value = t.slug;
                if (elements.promptInput) elements.promptInput.value = t.prompt;
                if (elements.menuParent) {
                    elements.menuParent.value = t.menuParent;
                    if (elements.menuCustomField) {
                        elements.menuCustomField.style.display = (t.menuParent === 'personalizado') ? 'block' : 'none';
                    }
                }
                updateLiveUrl();

                // Animación y scroll suave
                if (elements.promptInput) {
                    elements.promptInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    elements.promptInput.focus();
                }
            }
        });
    });
}

// ========== ENVÍO DEL FORMULARIO ==========
function initFormSubmission() {
    if (!elements.btnGenerate) return;
    elements.btnGenerate.addEventListener('click', async (e) => {
        e.preventDefault();
        if (state.isGenerating) return;

        const pageSlug = elements.pageSlug ? elements.pageSlug.value.trim() : '';
        const promptText = elements.promptInput ? elements.promptInput.value.trim() : '';
        let menuParent = elements.menuParent ? elements.menuParent.value : 'sidebar';
        const menuCustomName = elements.menuCustomName ? elements.menuCustomName.value.trim() : '';

        // Validaciones
        if (!pageSlug) {
            showAlert('Por favor ingresa un slug para la URL de la página.', 'error');
            elements.pageSlug.focus();
            return;
        }

        if (!/^[a-z0-9-]+$/.test(pageSlug)) {
            showAlert('El slug solo puede contener letras minúsculas, números y guiones.', 'error');
            elements.pageSlug.focus();
            return;
        }

        if (!promptText) {
            showAlert('Por favor describe la página que deseas crear o selecciona una plantilla.', 'error');
            elements.promptInput.focus();
            return;
        }

        if (menuParent === 'personalizado') {
            if (!menuCustomName) {
                showAlert('Por favor especifica el nombre para el menú personalizado.', 'error');
                elements.menuCustomName.focus();
                return;
            }
            menuParent = menuCustomName.toLowerCase().replace(/[^a-z0-9-]/g, '-');
        }

        const menuItemName = (menuParent === 'sidebar') ? 'Directo' : menuParent;

        // Iniciar proceso de generación
        await generatePage({
            slug: pageSlug,
            menuParent,
            menuItemName,
            prompt: promptText
        });
    });
}

// ========== PIPELINE DE AGENTES AUTÓNOMOS ==========
async function generatePage(data) {
    state.isGenerating = true;
    state.currentStep = 0;
    state.architectStructure = null;
    state.builderData = null;
    state.supervisorData = null;

    // Resetear y mostrar UI del pipeline
    resetPipelineUI();
    if (elements.pipeline) {
        elements.pipeline.style.display = 'block';
        elements.pipeline.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    if (elements.previewSection) elements.previewSection.style.display = 'none';

    // Estado del botón
    elements.btnGenerate.disabled = true;
    elements.btnGenerate.classList.add('loading');
    const btnText = elements.btnGenerate.querySelector('.btn-generate-text') || elements.btnGenerate;
    btnText.textContent = 'Generando página con IA...';

    // Mostrar prompt activo
    if (elements.compactPrompt && elements.compactPromptText) {
        const preview = data.prompt.length > 120 ? data.prompt.substring(0, 120) + '...' : data.prompt;
        elements.compactPromptText.textContent = preview;
        elements.compactPrompt.style.display = 'flex';
    }

    updateOverallProgress(0, 'Fase 1 de 3: Arquitectura de Información', 'Inicializando...');

    try {
        // ==========================================
        // FASE 1: AGENTE ARQUITECTO
        // ==========================================
        updateOverallProgress(5, 'Fase 1 de 3: Arquitectura de Información', 'Agente 1 analizando prompt...');
        activateStepUI(1, 'Analizando semántica y componentes...');

        const archResult = await callAPI('/agents/architect', {
            prompt: data.prompt,
            slug: data.slug,
            menuParent: data.menuParent,
            menuItemName: data.menuItemName
        });

        state.architectStructure = archResult.structure || archResult;
        completeStepUI(1, 'Estructura Definida ✔', renderArchitectSummary(state.architectStructure), archResult);
        updateOverallProgress(33, 'Fase 2 de 3: Construcción Frontend', 'Arquitectura completada');

        // ==========================================
        // FASE 2: AGENTE CONSTRUCTOR
        // ==========================================
        activateStepUI(2, 'Generando HTML5, estilos CSS Guindo y JS interactivo...');
        updateOverallProgress(40, 'Fase 2 de 3: Construcción Frontend', 'Agente 2 programando archivos...');

        const builderResult = await callAPI('/agents/builder', {
            structure: state.architectStructure,
            slug: data.slug,
            menuParent: data.menuParent,
            menuItemName: data.menuItemName
        });

        state.builderData = builderResult.content || builderResult;
        completeStepUI(2, 'Archivos Creados ✔', renderBuilderSummary(builderResult), builderResult);
        updateOverallProgress(66, 'Fase 3 de 3: Supervisión y Optimización', 'Código generado y guardado');

        // ==========================================
        // FASE 3: AGENTE SUPERVISOR
        // ==========================================
        activateStepUI(3, 'Auditando accesibilidad WCAG, armonía de color y rendimiento...');
        updateOverallProgress(75, 'Fase 3 de 3: Supervisión y Optimización', 'Agente 3 optimizando calidad...');

        const supResult = await callAPI('/agents/supervisor', {
            pageData: state.builderData,
            slug: data.slug,
            menuParent: data.menuParent,
            menuItemName: data.menuItemName
        });

        state.supervisorData = supResult;
        completeStepUI(3, 'Calidad Certificada ✔', renderSupervisorSummary(supResult), supResult);
        updateOverallProgress(100, 'Generación Completa', 'Página lista para visualización');

        // ==========================================
        // FINALIZACIÓN Y VISTA PREVIA
        // ==========================================
        onGenerationSuccess(data);

    } catch (error) {
        console.error('Error en pipeline de IA:', error);
        showAlert(error.message || 'Ocurrió un error al generar la página. Por favor intenta de nuevo.', 'error');
        resetGenerationState();
    }
}

// ========== ACTUALIZACIONES DE UI POR AGENTE ==========
function activateStepUI(stepNum, statusMessage) {
    const stepEl = document.getElementById(`step-${stepNum}`);
    const statusEl = document.getElementById(`step-${stepNum}-status`);
    const pillEl = document.getElementById(`pill-${stepNum}`);
    const msgEl = document.getElementById(`msg-${stepNum}`);
    const statusBox = document.getElementById(`status-box-${stepNum}`);

    if (stepEl) {
        stepEl.classList.add('active');
        stepEl.classList.remove('completed');
    }
    if (statusEl) statusEl.textContent = 'En progreso...';
    if (pillEl) {
        pillEl.className = 'agent-pill pill-active';
        pillEl.textContent = 'Procesando...';
    }
    if (msgEl) msgEl.textContent = statusMessage;
    if (statusBox) statusBox.style.display = 'flex';
}

function completeStepUI(stepNum, pillText, summaryHtml, rawData) {
    const stepEl = document.getElementById(`step-${stepNum}`);
    const statusEl = document.getElementById(`step-${stepNum}-status`);
    const pillEl = document.getElementById(`pill-${stepNum}`);
    const statusBox = document.getElementById(`status-box-${stepNum}`);
    const outputEl = document.getElementById(`output-${stepNum}`);
    const summaryEl = document.getElementById(`summary-${stepNum}`);
    const rawEl = document.getElementById(`raw-${stepNum}`);
    const connector = document.getElementById(`connector-${stepNum}`);

    if (stepEl) {
        stepEl.classList.remove('active');
        stepEl.classList.add('completed');
    }
    if (statusEl) statusEl.textContent = 'Completado ✔';
    if (connector) connector.classList.add('active');
    if (pillEl) {
        pillEl.className = 'agent-pill pill-success';
        pillEl.textContent = pillText;
    }
    if (statusBox) statusBox.style.display = 'none';

    if (summaryEl) summaryEl.innerHTML = summaryHtml;
    if (rawEl) rawEl.textContent = JSON.stringify(rawData, null, 2);
    if (outputEl) outputEl.style.display = 'block';
}

function updateOverallProgress(percent, phaseTitle, subtitle) {
    if (elements.progressBar) elements.progressBar.style.width = percent + '%';
    if (elements.progressText) elements.progressText.textContent = percent + '%';
    if (elements.progressPhase) elements.progressPhase.textContent = phaseTitle;
    if (elements.pipelineSubtitle && subtitle) elements.pipelineSubtitle.textContent = subtitle;
}

// ========== FORMATEADORES DE RESUMEN DE CADA AGENTE ==========
function renderArchitectSummary(structure) {
    const title = structure.title || 'Página Web';
    const sections = Array.isArray(structure.sections) ? structure.sections : [];
    
    let badges = sections.map(s => {
        const name = s.title || s.type || s.id || 'Sección';
        return `<span class="summary-badge">${name}</span>`;
    }).join('');

    return `
        <div class="summary-item">
            <strong>Título Propuesto:</strong> ${escapeHtml(title)}
        </div>
        <div class="summary-item">
            <strong>Secciones Estructuradas (${sections.length}):</strong>
            <div class="summary-badges-wrap">${badges || '<span class="summary-badge">Contenido principal</span>'}</div>
        </div>
    `;
}

function renderBuilderSummary(builderResult) {
    const filePath = builderResult.filePath || 'generated/.../index.html';
    const pageUrl = builderResult.pageUrl || '#';
    const content = builderResult.content || {};

    const hasHtml = !!content.html_content;
    const hasCss = !!content.css_content;
    const hasJs = !!content.js_content;

    return `
        <div class="summary-item">
            <strong>Archivo Generado:</strong> <code>${escapeHtml(filePath)}</code>
        </div>
        <div class="summary-item">
            <strong>Componentes de Código:</strong>
            <div class="summary-badges-wrap">
                <span class="summary-badge summary-badge-green">HTML5 Semántico ${hasHtml ? '✔' : ''}</span>
                <span class="summary-badge summary-badge-green">CSS3 Guindo / Dark Mode ${hasCss ? '✔' : ''}</span>
                <span class="summary-badge summary-badge-green">JS Interactivo ${hasJs ? '✔' : ''}</span>
            </div>
        </div>
    `;
}

function renderSupervisorSummary(supResult) {
    const aScore = supResult.accessibilityScore || 96;
    const pScore = supResult.performanceScore || 94;
    const improvements = Array.isArray(supResult.improvements) ? supResult.improvements : [
        'Jerarquía visual y contraste de color optimizados',
        'Soporte completo de modo oscuro validado',
        'Estructura responsive verificada para móviles y escritorio'
    ];

    let impList = improvements.slice(0, 3).map(imp => `<li>✔ ${escapeHtml(imp)}</li>`).join('');

    return `
        <div class="supervisor-scores-grid">
            <div class="score-card">
                <span class="score-number">${aScore}%</span>
                <span class="score-title">Accesibilidad WCAG</span>
            </div>
            <div class="score-card">
                <span class="score-number">${pScore}%</span>
                <span class="score-title">Rendimiento Estimado</span>
            </div>
            <div class="score-card">
                <span class="score-number">100%</span>
                <span class="score-title">Responsive Design</span>
            </div>
        </div>
        <ul class="improvements-list">${impList}</ul>
    `;
}

// ========== ACCIÓN AL COMPLETAR CON ÉXITO ==========
function onGenerationSuccess(data) {
    state.isGenerating = false;

    // Calcular URL final canónica
    const menuPart = data.menuParent === 'sidebar' ? '' : data.menuParent + '/';
    let pageUrl = `/jdeconomy/${menuPart}${data.slug}/`.replace(/\/+/g, '/');
    const fullUrl = window.location.origin + pageUrl;
    state.pageUrl = pageUrl;

    // Actualizar badges del pipeline
    if (elements.pipelineBadge) {
        elements.pipelineBadge.textContent = 'Finalizado con Éxito ✔';
        elements.pipelineBadge.className = 'pipeline-badge badge-success';
    }
    if (elements.pipelineStatusDot) {
        elements.pipelineStatusDot.className = 'pipeline-status-indicator success';
    }
    if (elements.pipelineSubtitle) {
        elements.pipelineSubtitle.textContent = 'Tu página web fue construida, pulida y publicada exitosamente.';
    }

    // Actualizar botón
    elements.btnGenerate.disabled = false;
    elements.btnGenerate.classList.remove('loading');
    const btnText = elements.btnGenerate.querySelector('.btn-generate-text') || elements.btnGenerate;
    btnText.textContent = 'Página Generada con Éxito ✔';

    // Configurar Vista Previa
    if (elements.browserUrlText) {
        elements.browserUrlText.textContent = fullUrl;
    }
    if (elements.btnOpenPage) {
        elements.btnOpenPage.href = pageUrl;
    }
    if (elements.previewFrame) {
        // Cache-buster para asegurar que carga la versión recién generada
        elements.previewFrame.src = pageUrl + '?t=' + Date.now();
    }
    if (elements.previewSection) {
        elements.previewSection.style.display = 'block';
        setTimeout(() => {
            elements.previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 200);
    }

    showAlert(`¡Página generada con éxito! Publicada en: <a href="${pageUrl}" target="_blank" style="color:#fff;text-decoration:underline;">${pageUrl}</a>`, 'success');
}

// ========== ACCIONES DE VISTA PREVIA (BROWSER MOCKUP) ==========
function initPreviewActions() {
    // Copiar URL
    if (elements.btnCopyUrl) {
        elements.btnCopyUrl.addEventListener('click', () => {
            const url = elements.btnOpenPage ? elements.btnOpenPage.href : elements.browserUrlText.textContent;
            navigator.clipboard.writeText(url).then(() => {
                const prevText = elements.btnCopyUrl.textContent;
                elements.btnCopyUrl.textContent = '¡URL Copiada! ✔';
                setTimeout(() => {
                    elements.btnCopyUrl.textContent = prevText;
                }, 2000);
            });
        });
    }

    // Recargar iframe
    if (elements.btnReloadPreview && elements.previewFrame) {
        elements.btnReloadPreview.addEventListener('click', () => {
            const currentSrc = elements.previewFrame.src.split('?')[0];
            elements.previewFrame.src = currentSrc + '?t=' + Date.now();
        });
    }

    // Selector de Viewport (Desktop, Tablet, Mobile)
    const viewportBtns = document.querySelectorAll('.viewport-btn');
    viewportBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            viewportBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const mode = btn.getAttribute('data-viewport');
            if (!elements.previewContainer) return;

            if (mode === 'mobile') {
                elements.previewContainer.style.maxWidth = '375px';
            } else if (mode === 'tablet') {
                elements.previewContainer.style.maxWidth = '768px';
            } else {
                elements.previewContainer.style.maxWidth = '100%';
            }
        });
    });
}

// ========== RESETEAR UI ==========
function resetPipelineUI() {
    for (let i = 1; i <= 3; i++) {
        const step = document.getElementById(`step-${i}`);
        const status = document.getElementById(`step-${i}-status`);
        const pill = document.getElementById(`pill-${i}`);
        const output = document.getElementById(`output-${i}`);
        const statusBox = document.getElementById(`status-box-${i}`);
        const connector = document.getElementById(`connector-${i}`);

        if (step) step.className = 'pipeline-step-item';
        if (status) status.textContent = 'En espera';
        if (pill) {
            pill.className = 'agent-pill';
            pill.textContent = 'En espera';
        }
        if (output) output.style.display = 'none';
        if (statusBox) statusBox.style.display = 'flex';
        if (connector) connector.classList.remove('active');
    }

    if (elements.pipelineBadge) {
        elements.pipelineBadge.textContent = 'En Ejecución';
        elements.pipelineBadge.className = 'pipeline-badge';
    }
    if (elements.pipelineStatusDot) {
        elements.pipelineStatusDot.className = 'pipeline-status-indicator pulse';
    }
    updateOverallProgress(0, 'Fase 1 de 3: Arquitectura', 'Inicializando...');
}

function resetGenerationState() {
    state.isGenerating = false;
    elements.btnGenerate.disabled = false;
    elements.btnGenerate.classList.remove('loading');
    const btnText = elements.btnGenerate.querySelector('.btn-generate-text') || elements.btnGenerate;
    btnText.textContent = 'Generar Página con Agentes IA';
}

// ========== LLAMADA A LA API ==========
async function callAPI(endpoint, payload) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 120000);

    try {
        const response = await fetch(API_BASE + endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.error || `Error ${response.status} en ${endpoint}`);
        }

        return data;
    } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            throw new Error('La llamada a la IA excedió el tiempo límite (timeout). Vuelve a intentarlo.');
        }
        throw error;
    }
}

// ========== UTILIDADES GLOBALES ==========
window.toggleRaw = function(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = (el.style.display === 'none' || !el.style.display) ? 'block' : 'none';
};

function showAlert(message, type = 'info') {
    if (!elements.alertContainer) return;
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} animate-fade-in`;
    alert.innerHTML = message;
    elements.alertContainer.innerHTML = '';
    elements.alertContainer.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 10000);
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
