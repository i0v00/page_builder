// JDEconomy AI Work - Aplicación Principal
// Gestiona el formulario, el pipeline de 3 agentes y la generación de páginas

const API_BASE = '/jdeconomy/api';

const state = {
    currentStep: 0,
    pageData: null,
    isGenerating: false,
    selectedTemplate: null
};

// Plantillas de ejemplo para la IA
const TEMPLATES = {
    consultoria: {
        slug: 'consultoria-financiera',
        menuParent: 'servicios',
        prompt: 'Crea una página profesional de servicios de consultoría financiera. Incluye una sección hero impactante con título, subtítulo y CTA. Una sección de descripción sobre los servicios ofrecidos. Tres features principales: asesoría tributaria, planificación financiera y auditoría. Una sección de testimonios con 3 clientes satisfechos. Un CTA final con botón de contacto. Footer con información de contacto. Usa diseño moderno, colores profesionales, tipografía elegante y modo claro/oscuro.'
    },
    academia: {
        slug: 'academia-online',
        menuParent: 'productos',
        prompt: 'Crea una página para una academia de cursos online. Incluye hero section con llamada a la acción, descripción de la academia, sección de cursos destacados con 4 cursos (2 de macroeconomía, 2 de ofimática), precios, testimonios de estudiantes y formulario de inscripción. Diseño profesional, responsive, accesible, con modo claro/oscuro y colores guindo.'
    },
    corporativo: {
        slug: 'quienes-somos',
        menuParent: 'nosotros',
        prompt: 'Crea una página corporativa "Nosotros" para una empresa. Incluye hero con misión y visión, sección de valores, equipo directivo con 3 miembros, servicios principales, y contacto. Diseño limpio, profesional, semántico HTML, accesible, responsive, modo claro/oscuro.'
    },
    ecommerce: {
        slug: 'productos',
        menuParent: 'sidebar',
        prompt: 'Crea una página de e-commerce con catálogo de productos. Incluye hero con ofertas, grid de productos con imágenes placeholder, precios, botón de agregar al carrito, filtros por categoría, y footer. Diseño moderno, responsive, accesible, modo claro/oscuro.'
    }
};

// Elementos DOM
const elements = {
    btnGenerate: document.getElementById('btn-generate'),
    pipeline: document.getElementById('agent-pipeline'),
    previewSection: document.getElementById('preview-section'),
    previewFrame: document.getElementById('preview-frame'),
    alertContainer: document.getElementById('alert-container'),
    btnCopyUrl: document.getElementById('btn-copy-url'),
    btnOpenPage: document.getElementById('btn-open-page'),
    menuPosition: document.getElementById('menu-parent'),
    menuParent: document.getElementById('menu-parent'),
    menuCustomField: document.getElementById('menu-custom-field'),
    menuCustomName: document.getElementById('menu-custom-name'),
    progressBar: document.getElementById('progress-bar'),
    progressText: document.getElementById('progress-text'),
    compactPrompt: document.getElementById('compact-prompt'),
    compactPromptText: document.getElementById('compact-prompt-text'),
    stepContents: {
        1: document.getElementById('content-1'),
        2: document.getElementById('content-2'),
        3: document.getElementById('content-3')
    },
    stepMessages: {
        1: document.getElementById('msg-1'),
        2: document.getElementById('msg-2'),
        3: document.getElementById('msg-3')
    },
    agentOutputs: {
        1: document.getElementById('raw-1'),
        2: document.getElementById('raw-2'),
        3: document.getElementById('raw-3')
    },
    agentOutputContainers: {
        1: document.getElementById('output-1'),
        2: document.getElementById('output-2'),
        3: document.getElementById('output-3')
    },
    steps: {
        1: document.getElementById('step-1'),
        2: document.getElementById('step-2'),
        3: document.getElementById('step-3')
    }
};

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMobileMenu();
    initMenuPositionToggle();
    initFormSubmission();
    initPreviewActions();
    initTemplates();
});

// ========== TEMA CLARO/OSCURO ==========
function initThemeToggle() {
    const btn = document.getElementById('theme-toggle');
    const sunIcon = document.getElementById('theme-sun');
    const moonIcon = document.getElementById('theme-moon');
    const savedTheme = localStorage.getItem('theme') || 'light';

    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        sunIcon.style.display = 'none';
        moonIcon.style.display = 'block';
    }

    btn.addEventListener('click', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark');
        localStorage.setItem('theme', isDark ? 'light' : 'dark');
        sunIcon.style.display = isDark ? 'block' : 'none';
        moonIcon.style.display = isDark ? 'none' : 'block';
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

// ========== CAMBIO DE POSICIÓN EN MENÚ ==========
function initMenuPositionToggle() {
    if (!elements.menuParent) return;
    elements.menuParent.addEventListener('change', (e) => {
        if (e.target.value === 'personalizado') {
            elements.menuCustomField.style.display = 'block';
            elements.menuCustomName.required = true;
        } else {
            elements.menuCustomField.style.display = 'none';
            elements.menuCustomName.required = false;
        }
    });
}

// ========== PLANTILLAS DE IA ==========
function initTemplates() {
    const templateCards = document.querySelectorAll('.template-card');
    templateCards.forEach(card => {
        card.addEventListener('click', () => {
            // Remover selección previa
            templateCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            const templateType = card.getAttribute('data-template');
            if (templateType && TEMPLATES[templateType]) {
                loadTemplate(templateType);
            }
        });
    });
}

window.loadTemplate = function(type) {
    const t = TEMPLATES[type];
    if (!t) return;

    document.getElementById('page-slug').value = t.slug;
    document.getElementById('prompt').value = t.prompt;
    document.getElementById('menu-parent').value = t.menuParent || 'sidebar';

    if (t.menuParent === 'personalizado') {
        document.getElementById('menu-custom-field').style.display = 'block';
    } else {
        document.getElementById('menu-custom-field').style.display = 'none';
    }

    // Marcar tarjeta seleccionada
    document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
    const card = document.querySelector(`[data-template="${type}"]`);
    if (card) card.classList.add('selected');

    // Scroll al prompt
    document.getElementById('prompt').scrollIntoView({ behavior: 'smooth' });
};

// ========== ENVÍO DEL FORMULARIO ==========
function initFormSubmission() {
    if (!elements.btnGenerate) return;
    elements.btnGenerate.addEventListener('click', async (e) => {
        e.preventDefault();

        const pageSlug = document.getElementById('page-slug').value.trim();
        const promptText = document.getElementById('prompt').value.trim();
        const menuParent = elements.menuParent ? elements.menuParent.value : 'sidebar';
        const menuCustomName = document.getElementById('menu-custom-name').value.trim();

        // Validación
        if (!pageSlug || !promptText) {
            showAlert('Por favor completa todos los campos obligatorios (slug de página y prompt).', 'error');
            return;
        }

        // Validar slug (solo alfanumérico y guiones)
        if (!/^[a-z0-9-]+$/.test(pageSlug)) {
            showAlert('El slug solo puede contener letras minúsculas, números y guiones.', 'error');
            return;
        }

        const menuItemName = menuParent === 'personalizado' ? menuCustomName : getMenuLabel(menuParent);

        if (menuParent === 'personalizado' && !menuItemName) {
            showAlert('Por favor ingresa el nombre del ítem personalizado para el menú.', 'error');
            return;
        }

        // Iniciar generación
        await generatePage({
            slug: pageSlug,
            menuParent,
            menuItemName,
            prompt: promptText
        });
    });
}

function getMenuLabel(value) {
    const labels = {
        'inicio': 'Inicio',
        'servicios': 'Servicios',
        'productos': 'Productos',
        'nosotros': 'Nosotros',
        'contacto': 'Contacto'
    };
    return labels[value] || value;
}

// ========== PIPELINE DE AGENTES ==========
async function generatePage(data) {
    state.isGenerating = true;
    state.currentStep = 0;

    // Reset UI
    resetPipelineUI();
    if (elements.pipeline) elements.pipeline.style.display = 'block';
    if (elements.previewSection) elements.previewSection.style.display = 'none';
    elements.btnGenerate.disabled = true;
    elements.btnGenerate.classList.add('loading');
    elements.btnGenerate.querySelector('span').textContent = 'Generando...';

    hideAlert();

    // Mostrar prompt compacto
    if (elements.compactPrompt && elements.compactPromptText) {
        const compact = data.prompt.length > 100 ? data.prompt.substring(0, 100) + '...' : data.prompt;
        elements.compactPromptText.textContent = compact;
        elements.compactPrompt.style.display = 'flex';
    }

    // Inicializar barra de progreso
    updateProgress(0);

    try {
        // Agente 1: Arquitecto
        await runAgent(1, 'Arquitecto de Página', async () => {
            updateStepMessage(1, 'Analizando el prompt y definiendo la estructura semántica...');
            const result = await callAPI('/agents/architect', {
                prompt: data.prompt,
                slug: data.slug,
                menuParent: data.menuParent,
                menuItemName: data.menuItemName
            });
            state.pageData = result;
            return result;
        });
        updateProgress(33);

        // Agente 2: Constructor
        await runAgent(2, 'Constructor de Archivos', async () => {
            updateStepMessage(2, 'Generando archivos HTML, CSS y JS...');
            const result = await callAPI('/agents/builder', {
                structure: state.pageData,
                slug: data.slug,
                menuParent: data.menuParent,
                menuItemName: data.menuItemName
            });
            state.pageData = { ...state.pageData, ...result };
            return result;
        });
        updateProgress(66);

        // Agente 3: Supervisor
        await runAgent(3, 'Supervisor / Mejorador', async () => {
            updateStepMessage(3, 'Revisando calidad de diseño, accesibilidad y rendimiento...');
            const result = await callAPI('/agents/supervisor', {
                pageData: state.pageData,
                slug: data.slug,
                menuParent: data.menuParent,
                menuItemName: data.menuItemName
            });
            state.pageData = { ...state.pageData, ...result };
            return result;
        });
        updateProgress(100);

        // Generación completa
        onGenerationComplete(data);

    } catch (error) {
        console.error('Error en generación:', error);
        showAlert(error.message || 'Ocurrió un error al generar la página. Intenta nuevamente.', 'error');
        resetGenerationUI();
    }
}

async function runAgent(stepNumber, agentName, agentFn) {
    state.currentStep = stepNumber;

    // Marcar paso como activo
    updateStepActive(stepNumber, true);

    // Mostrar contenido del paso
    const content = elements.stepContents[stepNumber];
    if (content) content.classList.add('visible');

    try {
        const result = await agentFn();

        // Marcar paso como completado
        updateStepActive(stepNumber, false);
        updateStepCompleted(stepNumber, true);

        return result;
    } catch (error) {
        updateStepActive(stepNumber, false);
        throw error;
    }
}

function updateProgress(percent) {
    if (elements.progressBar) elements.progressBar.style.width = percent + '%';
    if (elements.progressText) elements.progressText.textContent = percent + '%';
}

function showAgentOutput(stepNumber, result) {
    const outputEl = elements.agentOutputs[stepNumber];
    const container = elements.agentOutputContainers[stepNumber];
    if (!outputEl || !container) return;

    let displayText = '';
    if (result && result.structure) {
        displayText = JSON.stringify(result.structure, null, 2);
    } else if (result && result.content) {
        displayText = JSON.stringify(result.content, null, 2);
    } else if (result && result.improvements) {
        displayText = JSON.stringify(result, null, 2);
    } else {
        displayText = JSON.stringify(result, null, 2);
    }

    outputEl.textContent = displayText;
    container.style.display = 'block';
}

function updateStepActive(stepNumber, active) {
    const step = elements.steps[stepNumber];
    if (!step) return;
    const number = step.querySelector('.step-number');
    const label = step.querySelector('.step-label');

    if (active) {
        step.classList.add('active');
        step.classList.remove('completed');
        number.textContent = stepNumber;
        label.style.color = 'var(--color-primary)';
    } else {
        step.classList.remove('active');
    }
}

function updateStepCompleted(stepNumber, completed) {
    const step = elements.steps[stepNumber];
    if (!step) return;
    const number = step.querySelector('.step-number');

    if (completed) {
        step.classList.add('completed');
        number.textContent = '✔';
    }
}

function updateStepMessage(stepNumber, message) {
    const msgEl = elements.stepMessages[stepNumber];
    if (msgEl) {
        msgEl.textContent = message;
    }
}

function resetPipelineUI() {
    for (let i = 1; i <= 3; i++) {
        const step = elements.steps[i];
        if (!step) continue;
        step.classList.remove('active', 'completed');
        step.querySelector('.step-number').textContent = i;
        step.querySelector('.step-label').style.color = '';
        elements.stepContents[i].classList.remove('visible');
    }
    updateProgress(0);
    if (elements.compactPrompt) elements.compactPrompt.style.display = 'none';
    for (let i = 1; i <= 3; i++) {
        if (elements.agentOutputContainers[i]) {
            elements.agentOutputContainers[i].style.display = 'none';
        }
    }
}

function resetGenerationUI() {
    state.isGenerating = false;
    elements.btnGenerate.disabled = false;
    elements.btnGenerate.classList.remove('loading');
    elements.btnGenerate.querySelector('span').textContent = 'Generar Página con IA';
    if (elements.compactPrompt) elements.compactPrompt.style.display = 'none';
    updateProgress(0);
}

// ========== LLAMADA A LA API ==========
async function callAPI(endpoint, body) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 110000);

    try {
        const response = await fetch(API_BASE + endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body),
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `Error ${response.status} al llamar a ${endpoint}`);
        }

        return response.json();
    } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            throw new Error('La solicitud tardó demasiado y se canceló (timeout). Intenta nuevamente.');
        }
        throw error;
    }
}

// ========== COMPLETADO ==========
function onGenerationComplete(data) {
    const menuPart = data.menuParent === 'sidebar' ? '' : data.menuParent + '/';
    let pageUrl = `/jdeconomy/${menuPart}${data.slug}/`;
    pageUrl = pageUrl.replace(/\/+/g, '/');

    state.isGenerating = false;
    elements.btnGenerate.disabled = false;
    elements.btnGenerate.classList.remove('loading');
    elements.btnGenerate.querySelector('span').textContent = 'Página Generada ✔';

    // Mostrar vista previa
    elements.previewSection.style.display = 'block';
    elements.previewFrame.src = pageUrl + '?preview=1';
    elements.btnOpenPage.href = pageUrl;

    showAlert(
        `¡Página generada exitosamente! URL: ${pageUrl}`,
        'success'
    );

    // Hacer scroll a la vista previa
    elements.previewSection.scrollIntoView({ behavior: 'smooth' });
}

// ========== ACCIONES DE VISTA PREVIA ==========
function initPreviewActions() {
    if (!elements.btnCopyUrl) return;
    elements.btnCopyUrl.addEventListener('click', () => {
        const url = elements.btnOpenPage.href;
        navigator.clipboard.writeText(url).then(() => {
            const originalText = elements.btnCopyUrl.textContent;
            elements.btnCopyUrl.textContent = '¡Copiado!';
            setTimeout(() => {
                elements.btnCopyUrl.textContent = originalText;
            }, 2000);
        });
    });
}

// ========== ALERTAS ==========
function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    elements.alertContainer.innerHTML = '';
    elements.alertContainer.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 8000);
}

function hideAlert() {
    elements.alertContainer.innerHTML = '';
}