<?php
// JDEconomy AI Work - Configuración de Cloudflare AI Workers
// Lee las variables de entorno desde .env

// Cargar variables de entorno desde .env si existe
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = preg_replace('/^["\'](.*)["\']$/', '$1', $value);
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

define('CLOUDFLARE_ACCOUNT_ID', getenv('CLOUDFLARE_ACCOUNT_ID') ?: '');
define('CLOUDFLARE_API_TOKEN', getenv('CLOUDFLARE_API_TOKEN') ?: '');
define('CLOUDFLARE_AI_GATEWAY', getenv('CLOUDFLARE_AI_GATEWAY') ?: '');

define('CLOUDFLARE_API_BASE', 'https://api.cloudflare.com/client/v4/accounts/');

// Modelos especializados por tarea
define('MODEL_ARCHITECT', '@cf/meta/llama-3.1-70b-instruct');
define('MODEL_BUILDER', '@cf/qwen/qwen2.5-coder-32b-instruct');
define('MODEL_SUPERVISOR', '@cf/meta/llama-3.1-70b-instruct');

define('PROJECT_BASE', '/jdeconomy');
define('GENERATED_DIR', dirname(__DIR__) . '/generated');

// Cabeceras CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Validar configuración de Cloudflare
function validateCloudflareConfig() {
    if (empty(CLOUDFLARE_ACCOUNT_ID) || empty(CLOUDFLARE_API_TOKEN)) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Configuración de Cloudflare incompleta. Revisa el archivo .env con CLOUDFLARE_ACCOUNT_ID y CLOUDFLARE_API_TOKEN.'
        ]);
        exit;
    }
}

// Llamar a Cloudflare Workers AI con soporte de fallback automático
function callCloudflareAI($model, $prompt, $maxTokens = 4000) {
    validateCloudflareConfig();

    try {
        return executeCloudflareRequest($model, $prompt, $maxTokens);
    } catch (Exception $e) {
        // Si el modelo especializado falla, intentar con Llama 3.1 70B como fallback
        if ($model !== '@cf/meta/llama-3.1-70b-instruct') {
            return executeCloudflareRequest('@cf/meta/llama-3.1-70b-instruct', $prompt, $maxTokens);
        }
        throw $e;
    }
}

function executeCloudflareRequest($model, $prompt, $maxTokens = 4000) {
    $url = CLOUDFLARE_API_BASE . CLOUDFLARE_ACCOUNT_ID . '/ai/run/' . $model;

    $payload = [
        'prompt' => $prompt,
        'max_tokens' => $maxTokens,
        'temperature' => 0.7
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . CLOUDFLARE_API_TOKEN,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('Error de conexión con Cloudflare AI: ' . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception('Cloudflare AI retornó código HTTP ' . $httpCode . ': ' . $response);
    }

    $data = json_decode($response, true);

    if (!isset($data['result']['response'])) {
        throw new Exception('Respuesta inesperada de Cloudflare AI: ' . $response);
    }

    return $data['result']['response'];
}

// Sanitizar slug para uso en URLs y nombres de archivos
function sanitizeSlug($slug) {
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'pagina';
}

// Extraer objeto JSON de una respuesta que puede incluir texto extra o bloques markdown
function extractJsonFromResponse($response) {
    $response = trim($response);
    // Quitar bloques de codigo markdown
    $response = preg_replace('/^```(?:json|html)?\s*/i', '', $response);
    $response = preg_replace('/\s*```\s*$/i', '', $response);
    $response = trim($response);

    // Intentar decodificar directamente
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        return $data;
    }

    // Buscar el primer { y el ultimo } para extraer solo el objeto JSON
    $start = strpos($response, '{');
    $end = strrpos($response, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $json = substr($response, $start, $end - $start + 1);
        $data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        // Quitar comas colgantes (trailing commas) comunes en LLMs
        $noTrailing = preg_replace('/,\s*([}\]])/', '$1', $json);
        $data = json_decode($noTrailing, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        // Limpiar caracteres de control no escapados dentro de cadenas (saltos de línea crudos)
        $cleaned = preg_replace('/[\x00-\x1F\x7F]/', ' ', $noTrailing);
        $data = json_decode($cleaned, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }
    }

    // Fallback: extracción por expresiones regulares si el JSON está mal formateado por el LLM
    $extracted = [];
    if (preg_match('/"pageTitle"\s*:\s*"([^"]+)"/s', $response, $m)) {
        $extracted['pageTitle'] = $m[1];
    }
    if (preg_match('/"html_content"\s*:\s*"(.*?)(?:",\s*"css_content"|",\s*"js_content"|",\s*"pageTitle"|"\s*})/s', $response, $m)) {
        $extracted['html_content'] = stripslashes($m[1]);
    }
    if (preg_match('/"css_content"\s*:\s*"(.*?)(?:",\s*"js_content"|",\s*"pageTitle"|",\s*"improvements"|"\s*})/s', $response, $m)) {
        $extracted['css_content'] = stripslashes($m[1]);
    }
    if (preg_match('/"js_content"\s*:\s*"(.*?)(?:",\s*"pageTitle"|",\s*"improvements"|"\s*})/s', $response, $m)) {
        $extracted['js_content'] = stripslashes($m[1]);
    }

    if (!empty($extracted['html_content']) || !empty($extracted['pageTitle'])) {
        return $extracted;
    }

    throw new Exception('Respuesta no es JSON valido: ' . json_last_error_msg());
}

// Obtener label del menú
function getMenuLabel($position, $customName = '') {
    $labels = [
        'inicio' => 'Inicio',
        'servicios' => 'Servicios',
        'productos' => 'Productos',
        'nosotros' => 'Nosotros',
        'contacto' => 'Contacto',
        'personalizado' => $customName ?: 'Personalizado'
    ];
    return $labels[$position] ?? $position;
}

// Generar HTML completo de una página
function generatePageHTML($pageData) {
    $slug = htmlspecialchars($pageData['slug'] ?? '');
    $menuParent = $pageData['menuParent'] ?? 'sidebar';
    $menuItemName = $pageData['menuItemName'] ?? '';
    $title = htmlspecialchars($pageData['title'] ?? 'Página Generada');
    
    $bodyContent = $pageData['html_content'] ?? '';
    $cssContent = $pageData['css_content'] ?? '';
    $jsContent = $pageData['js_content'] ?? '';

    // Limpieza de Markdown y etiquetas duplicadas
    $bodyContent = preg_replace('/^```(?:html)?\s*/i', '', trim($bodyContent));
    $bodyContent = preg_replace('/\s*```\s*$/i', '', $bodyContent);
    $bodyContent = trim($bodyContent);

    // Si la IA envolvió todo en <main>, extraer el interior
    if (preg_match('/<main[^>]*>(.*?)<\/main>/is', $bodyContent, $matches)) {
        $bodyContent = $matches[1];
    }
    // Eliminar headers internos duplicados
    $bodyContent = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $bodyContent);
    // Eliminar footers internos duplicados
    $bodyContent = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $bodyContent);

    // Obtener menú dinámico de la base de datos
    $navLinksHtml = '';
    try {
        require_once dirname(__DIR__) . '/config/db.php';
        if (isset($pdo)) {
            $stmt = $pdo->query("SELECT name, slug, url FROM menu_items WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order ASC, name ASC LIMIT 6");
            $dbMenus = $stmt->fetchAll();
            foreach ($dbMenus as $dm) {
                $isActive = ($dm['slug'] === $menuParent);
                $navLinksHtml .= sprintf(
                    '<a href="%s" class="nav-link%s">%s</a>',
                    htmlspecialchars($dm['url']),
                    $isActive ? ' active' : '',
                    htmlspecialchars($dm['name'])
                );
            }
        }
    } catch (Exception $e) {
        $navLinksHtml = '<a href="/jdeconomy/" class="nav-link">Inicio</a><a href="/jdeconomy/macroeconomia/" class="nav-link">Macroeconomía</a><a href="/jdeconomy/ofimatica/" class="nav-link">Ofimática</a>';
    }
    $navLinksHtml .= '<a href="/jdeconomy/ai-work/" class="nav-link">AI Work</a>';

    $htmlTemplate = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{PAGE_TITLE}} - JDEconomy</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
:root {
    --color-primary: #6B1E2A;
    --color-primary-dark: #4A1520;
    --color-primary-light: #8B2E3C;
    --color-primary-gradient: linear-gradient(135deg, #6B1E2A 0%, #4A1520 100%);
    --color-bg: #FAF7F7;
    --color-surface: #FFFFFF;
    --color-text-main: #1A1A1A;
    --color-text-secondary: #5C5C5C;
    --color-borders: #E8E0E0;
    --radius-sm: 0.375rem;
    --radius-md: 0.5rem;
    --radius-lg: 0.75rem;
    --radius-xl: 1rem;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.08);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
    --transition-base: 0.3s ease;
}
[data-theme="dark"] {
    --color-primary: #C45C6A;
    --color-primary-dark: #A34452;
    --color-primary-light: #E07A88;
    --color-bg: #121212;
    --color-surface: #1E1E1E;
    --color-text-main: #F5F5F5;
    --color-text-secondary: #B0B0B0;
    --color-borders: #333333;
    --color-primary-gradient: linear-gradient(135deg, #C45C6A 0%, #A34452 100%);
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
    background-color: var(--color-bg);
    color: var(--color-text-main);
    line-height: 1.6;
    transition: background-color var(--transition-base), color var(--transition-base);
}
.header {
    background-color: var(--color-surface);
    border-bottom: 1px solid var(--color-borders);
    position: sticky;
    top: 0;
    z-index: 100;
}
.header-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0.85rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: var(--color-primary);
    font-weight: 700;
    font-size: 1.25rem;
}
.logo-mark {
    width: 36px;
    height: 36px;
    background: var(--color-primary-gradient);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
}
.nav-menu {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.nav-link {
    color: var(--color-text-secondary);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}
.nav-link:hover, .nav-link.active {
    color: var(--color-primary);
    background-color: rgba(107, 30, 42, 0.08);
}
.theme-toggle {
    background: none;
    border: 1px solid var(--color-borders);
    border-radius: 8px;
    width: 38px;
    height: 38px;
    cursor: pointer;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-main);
}
main {
    min-height: 70vh;
}
footer {
    background-color: var(--color-surface);
    border-top: 1px solid var(--color-borders);
    padding: 2.5rem 1.5rem;
    text-align: center;
    margin-top: 3rem;
    color: var(--color-text-secondary);
}
footer .footer-logo {
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 0.5rem;
}
{{CSS_CONTENT}}
    </style>
</head>
<body>
    <header class="header generated-menu">
        <div class="header-inner">
            <a href="/jdeconomy/" class="logo">
                <div class="logo-mark">JD</div>
                <div class="logo-text">JDEconomy <span style="font-weight:400; font-size:0.85rem; opacity:0.7;">Academia</span></div>
            </a>
            <nav class="nav-menu">
                {{NAV_LINKS}}
            </nav>
            <nav class="nav-actions">
                <button class="theme-toggle" aria-label="Cambiar tema">
                    <span class="theme-icon">☀️</span>
                </button>
            </nav>
        </div>
    </header>

    <main>
{{BODY_CONTENT}}
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">JDEconomy Academia</div>
            <div class="footer-text">Página generada con JDEconomy AI Work &copy; 2026</div>
        </div>
    </footer>

    <script>
{{JS_CONTENT}}
        // Inicializar tema
        (function() {
            const btn = document.querySelector('.theme-toggle');
            if (!btn) return;
            const icon = btn.querySelector('.theme-icon');
            const saved = localStorage.getItem('theme') || 'light';
            if (saved === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                if (icon) icon.textContent = '🌙';
            }
            btn.addEventListener('click', function() {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark');
                localStorage.setItem('theme', isDark ? 'light' : 'dark');
                if (icon) icon.textContent = isDark ? '☀️' : '🌙';
            });
        })();
    </script>
</body>
</html>
HTML;

    return str_replace(
        ['{{PAGE_TITLE}}', '{{CSS_CONTENT}}', '{{NAV_LINKS}}', '{{BODY_CONTENT}}', '{{JS_CONTENT}}'],
        [$title, $cssContent, $navLinksHtml, $bodyContent, $jsContent],
        $htmlTemplate
    );
}

// Router principal
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$apiBase = PROJECT_BASE . '/api/';

// Remover prefijo de API para obtener el endpoint
$endpoint = str_replace($apiBase, '', $requestUri);
$endpoint = trim($endpoint, '/');

// Rutas disponibles
if ($endpoint === 'agents/architect') {
    handleArchitect();
} elseif ($endpoint === 'agents/builder') {
    handleBuilder();
} elseif ($endpoint === 'agents/supervisor') {
    handleSupervisor();
} elseif ($endpoint === 'agents/stream') {
    handleStreamAgent();
} elseif ($endpoint === 'pages') {
    handlePages($requestMethod);
} elseif ($endpoint === 'menus') {
    handleMenus($requestMethod);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint no encontrado: ' . $endpoint]);
}

// ========== AGENTE 1: ARQUITECTO ==========
function handleArchitect() {
    $input = json_decode(file_get_contents('php://input'), true);
    $prompt = $input['prompt'] ?? '';
    $slug = $input['slug'] ?? '';
    $menuParent = $input['menuParent'] ?? 'sidebar';
    $menuItemName = $input['menuItemName'] ?? '';

    if (!$prompt) {
        http_response_code(400);
        echo json_encode(['error' => 'Prompt es requerido']);
        return;
    }

    $architectPrompt =
        "Eres el Arquitecto de Paginas de JDEconomy AI Work, un experto en diseno web y arquitectura de informacion.\n\n" .
        "Tu tarea es analizar el siguiente prompt del usuario y definir la estructura completa de una pagina web.\n\n" .
        "PROMPT DEL USUARIO:\n" . $prompt . "\n\n" .
        "DATOS DE LA PAGINA:\n" .
        "- Slug: " . $slug . "\n" .
        "- Menu Padre: " . $menuParent . "\n" .
        "- Nombre del item: " . $menuItemName . "\n\n" .
        "Para cada seccion, define:\n" .
        "- Titulo de la seccion\n" .
        "- Contenido textual completo\n" .
        "- Tipo de contenido (texto, imagen, lista, etc.)\n" .
        "- Clases CSS sugeridas\n" .
        "- Orden de aparicion\n\n" .
        "Tambien define:\n" .
        "- Titulo de la pagina (title)\n" .
        "- Descripcion meta\n" .
        "- Estructura semantica HTML completa\n\n" .
        "Responde UNICAMENTE con un objeto JSON valido con la siguiente estructura:\n" .
        '{
  "title": "Titulo de la pagina",
  "metaDescription": "Descripcion meta",
  "sections": [
    {
      "id": "hero",
      "type": "hero",
      "title": "Titulo del hero",
      "subtitle": "Subtitulo",
      "ctaText": "Texto del boton CTA",
      "ctaLink": "#",
      "background": "gradient o image",
      "content": "Contenido principal"
    },
    {
      "id": "description",
      "type": "description",
      "title": "Titulo",
      "content": "Contenido de descripcion"
    },
    {
      "id": "features",
      "type": "features",
      "title": "Nuestros Servicios",
      "items": [
        {
          "title": "Titulo del feature",
          "description": "Descripcion",
          "icon": "icono sugerido"
        }
      ]
    },
    {
      "id": "testimonials",
      "type": "testimonials",
      "title": "Testimonios",
      "items": [
        {
          "text": "Texto del testimonio",
          "author": "Autor",
          "role": "Cargo"
        }
      ]
    },
    {
      "id": "cta",
      "type": "cta",
      "title": "CTA Final",
      "content": "Texto del CTA",
      "buttonText": "Texto del boton",
      "buttonLink": "#"
    },
    {
      "id": "footer",
      "type": "footer",
      "content": "Contenido del footer"
    }
  ],
  "designPreferences": {
    "colorScheme": "guindo",
    "typography": "moderna",
    "layout": "limpio y profesional"
  }
}' . "\n\n" .
        "No incluyas texto adicional antes o despues del JSON. Solo el JSON valido.";

    try {
        $response = callCloudflareAI(MODEL_ARCHITECT, $architectPrompt, 6000);
        $data = extractJsonFromResponse($response);

        echo json_encode([
            'success' => true,
            'agent' => 'architect',
            'structure' => $data
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error en Agente Arquitecto: ' . $e->getMessage()
        ]);
    }
}

// ========== AGENTE 2: CONSTRUCTOR ==========
function handleBuilder() {
    $input = json_decode(file_get_contents('php://input'), true);
    $structure = $input['structure']['structure'] ?? $input['structure'] ?? [];
    $slug = sanitizeSlug($input['slug'] ?? 'pagina');
    $menuParent = $input['menuParent'] ?? 'sidebar';
    $menuItemName = $input['menuItemName'] ?? '';

    if (empty($structure)) {
        http_response_code(400);
        echo json_encode(['error' => 'Estructura de pagina es requerida']);
        return;
    }

    $builderPrompt =
        "Eres el Constructor de Archivos de JDEconomy AI Work, un experto desarrollador frontend.\n\n" .
        "Tu tarea es generar los archivos reales (HTML, CSS, JS) para una pagina web basada en la estructura proporcionada.\n\n" .
        "ESTRUCTURA DE LA PAGINA (del Agente Arquitecto):\n" . json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n" .
        "DATOS:\n" .
        "- Slug: " . $slug . "\n" .
        "- Menu Padre: " . $menuParent . "\n" .
        "- Nombre del item: " . $menuItemName . "\n\n" .
        "REQUISITOS TECNICOS:\n" .
        "1. NO incluyas <header>, <nav> ni <footer> principales del sitio en html_content (la plantilla base ya los provee). Solo genera las secciones <section> con el contenido.\n" .
        "2. HTML semantico y accesible (etiquetas section, article, h1, h2, p, button, etc.)\n" .
        "3. CSS moderno con variables CSS (paleta de colores Guindo: #6B1E2A primario, #FAF7F7 fondo claro, #121212 fondo oscuro)\n" .
        "4. Soporte completo de modo claro/oscuro con data-theme\n" .
        "5. Diseno responsive desde 320px hasta pantallas grandes\n" .
        "6. Transiciones suaves, sombras sutiles, bordes redondeados moderados\n" .
        "7. Tipografia: Plus Jakarta Sans o Inter\n" .
        "8. Accesibilidad: contrastes WCAG, focus visible, aria labels, roles semanticos\n\n" .
        "GENERA:\n" .
        "1. HTML completo y semantico para el contenido de la pagina (solo etiquetas <section>, sin <header>, <footer>, <html>, <head> ni <body>)\n" .
        "2. CSS completo con variables CSS para modo claro/oscuro\n" .
        "3. JS minimo necesario (interacciones, modales, animaciones si aplica)\n\n" .
        "Responde UNICAMENTE con un objeto JSON valido:\n" .
        '{
  "html_content": "<section>...</section><section>...</section>",
  "css_content": ":root { ... } [css completo]",
  "js_content": "// JavaScript minimo",
  "pageTitle": "Titulo de la pagina",
  "filePath": "' . $slug . '/index.html"
}' . "\n\n" .
        "No incluyas texto adicional antes o despues del JSON. Solo el JSON valido.";

    try {
        $response = callCloudflareAI(MODEL_BUILDER, $builderPrompt, 8000);
        $data = extractJsonFromResponse($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Respuesta no es JSON valido: ' . json_last_error_msg());
        }

        // Crear directorio y guardar archivo
        $pageDir = GENERATED_DIR . '/' . $menuParent . '/' . $slug;
        if (!is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }

        $pageHtml = generatePageHTML([
            'slug' => $slug,
            'menuParent' => $menuParent,
            'menuItemName' => $menuItemName,
            'title' => $data['pageTitle'] ?? 'Pagina',
            'html_content' => $data['html_content'] ?? '',
            'css_content' => $data['css_content'] ?? '',
            'js_content' => $data['js_content'] ?? ''
        ]);

        $filePath = $pageDir . '/index.html';
        file_put_contents($filePath, $pageHtml);

        // Tambien guardar CSS y JS como archivos separados para referencia
        if (!empty($data['css_content'])) {
            file_put_contents($pageDir . '/style.css', $data['css_content']);
        }
        if (!empty($data['js_content'])) {
            file_put_contents($pageDir . '/script.js', $data['js_content']);
        }

        $pageUrl = $menuParent === 'sidebar' 
            ? PROJECT_BASE . '/' . $slug . '/' 
            : PROJECT_BASE . '/' . $menuParent . '/' . $slug . '/';

        echo json_encode([
            'success' => true,
            'agent' => 'builder',
            'filePath' => $filePath,
            'pageUrl' => $pageUrl,
            'content' => $data
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error en Agente Constructor: ' . $e->getMessage()
        ]);
    }
}

// ========== AGENTE 3: SUPERVISOR ==========
function handleSupervisor() {
    $input = json_decode(file_get_contents('php://input'), true);
    $pageData = $input['pageData']['content'] ?? $input['pageData'] ?? [];
    $slug = sanitizeSlug($input['slug'] ?? 'pagina');
    $menuParent = $input['menuParent'] ?? 'sidebar';
    $menuItemName = $input['menuItemName'] ?? '';

    $supervisorPrompt =
        "Eres el Supervisor de Calidad de JDEconomy AI Work, un experto en UX/UI design, accesibilidad y rendimiento web.\n\n" .
        "Tu tarea es revisar el trabajo de los dos agentes anteriores (Arquitecto y Constructor) y mejorar la calidad del diseno, la coherencia visual, la tipografia, el espaciado y la experiencia de usuario.\n\n" .
        "DATOS DE LA PAGINA:\n" .
        "- Slug: " . $slug . "\n" .
        "- Menu Padre: " . $menuParent . "\n" .
        "- Nombre del item: " . $menuItemName . "\n\n" .
        "DATOS DE LA PAGINA GENERADA:\n" . json_encode($pageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n" .
        "REVISA Y MEJORA:\n" .
        "1. NO incluyas <header>, <nav> ni <footer> principales del sitio en html_content (la plantilla base ya los provee). Solo genera las secciones <section> con el contenido.\n" .
        "2. Calidad del diseno y coherencia visual con paleta Guindo (#6B1E2A)\n" .
        "3. Tipografia y jerarquia de tamaños legible\n" .
        "4. Espaciado generoso y limpio\n" .
        "5. Accesibilidad (contraste WCAG, focus, aria labels, roles semanticos)\n" .
        "6. Rendimiento (CSS optimizado, JS eficiente)\n" .
        "7. Modo claro/oscuro correctamente implementado\n" .
        "8. Responsividad completa\n\n" .
        "Responde UNICAMENTE con un objeto JSON valido con las mejoras aplicadas:\n" .
        '{
  "html_content": "<section>...</section>",
  "css_content": "[CSS mejorado con variables]",
  "js_content": "[JS mejorado]",
  "improvements": ["lista de mejoras aplicadas"],
  "accessibilityScore": 96,
  "performanceScore": 94,
  "pageTitle": "Titulo final de la pagina"
}' . "\n\n" .
        "No incluyas texto adicional antes o despues del JSON. Solo el JSON valido.";

    try {
        $response = callCloudflareAI(MODEL_SUPERVISOR, $supervisorPrompt, 8000);
        $data = extractJsonFromResponse($response);

        // Regenerar el archivo HTML con las mejoras del supervisor
        $pageDir = GENERATED_DIR . '/' . $menuParent . '/' . $slug;
        if (!is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }

        $pageHtml = generatePageHTML([
            'slug' => $slug,
            'menuParent' => $menuParent,
            'menuItemName' => $menuItemName,
            'title' => $data['pageTitle'] ?? 'Pagina',
            'html_content' => $data['html_content'] ?? '',
            'css_content' => $data['css_content'] ?? '',
            'js_content' => $data['js_content'] ?? ''
        ]);

        $filePath = $pageDir . '/index.html';
        file_put_contents($filePath, $pageHtml);

        $pageUrl = $menuParent === 'sidebar'
            ? PROJECT_BASE . '/' . $slug . '/'
            : PROJECT_BASE . '/' . $menuParent . '/' . $slug . '/';

        echo json_encode([
            'success' => true,
            'agent' => 'supervisor',
            'filePath' => $filePath,
            'pageUrl' => $pageUrl,
            'improvements' => $data['improvements'] ?? [],
            'accessibilityScore' => $data['accessibilityScore'] ?? 0,
            'performanceScore' => $data['performanceScore'] ?? 0,
            'content' => $data
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error en Agente Supervisor: ' . $e->getMessage()
        ]);
    }
}

// ========== AGENTE STREAMING ==========
function handleStreamAgent() {
    $input = json_decode(file_get_contents('php://input'), true);
    $agent = $input['agent'] ?? 'architect';
    $prompt = $input['prompt'] ?? '';
    $structure = $input['structure'] ?? [];
    $pageData = $input['pageData'] ?? [];
    $slug = $input['slug'] ?? '';
    $menuParent = $input['menuParent'] ?? 'sidebar';
    $menuItemName = $input['menuItemName'] ?? '';

    if (!$prompt && empty($structure) && empty($pageData)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos requeridos para streaming']);
        return;
    }

    // Construir el prompt según el agente
    if ($agent === 'architect') {
        $fullPrompt = buildArchitectPrompt($prompt, $slug, $menuParent, $menuItemName);
        $model = MODEL_ARCHITECT;
        $maxTokens = 6000;
    } elseif ($agent === 'builder') {
        $fullPrompt = buildBuilderPrompt($structure, $slug, $menuParent, $menuItemName);
        $model = MODEL_BUILDER;
        $maxTokens = 8000;
    } elseif ($agent === 'supervisor') {
        $fullPrompt = buildSupervisorPrompt($pageData, $slug, $menuParent, $menuItemName);
        $model = MODEL_SUPERVISOR;
        $maxTokens = 8000;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Agente no valido: ' . $agent]);
        return;
    }

    // Headers SSE para streaming
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache, no-transform');
    header('X-Accel-Buffering: no');
    header('Access-Control-Allow-Origin: *');

    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    ob_implicit_flush(1);

    try {
        streamCloudflareAI($model, $fullPrompt, $maxTokens);
    } catch (Exception $e) {
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
        flush();
    }
}

function streamCloudflareAI($model, $prompt, $maxTokens = 4000) {
    validateCloudflareConfig();

    $url = CLOUDFLARE_API_BASE . CLOUDFLARE_ACCOUNT_ID . '/ai/run/' . $model;

    $payload = [
        'prompt' => $prompt,
        'max_tokens' => $maxTokens,
        'temperature' => 0.7,
        'stream' => true
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . CLOUDFLARE_API_TOKEN,
        'Content-Type: application/json',
        'Accept: text/event-stream'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) {
        echo $chunk;
        flush();
        return strlen($chunk);
    });

    $result = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('Error de streaming con Cloudflare AI: ' . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception('Cloudflare AI streaming retornó HTTP ' . $httpCode);
    }
}

// Construir prompt del arquitecto
function buildArchitectPrompt($prompt, $slug, $menuParent, $menuItemName) {
    return
        "Eres el Arquitecto de Paginas de JDEconomy AI Work, un experto en diseno web y arquitectura de informacion.\n\n" .
        "Tu tarea es analizar el siguiente prompt del usuario y definir la estructura completa de una pagina web.\n\n" .
        "PROMPT DEL USUARIO:\n" . $prompt . "\n\n" .
        "DATOS DE LA PAGINA:\n" .
        "- Slug: " . $slug . "\n" .
        "- Menu Padre: " . $menuParent . "\n" .
        "- Nombre del item: " . $menuItemName . "\n\n" .
        "Para cada seccion, define:\n" .
        "- Titulo de la seccion\n" .
        "- Contenido textual completo\n" .
        "- Tipo de contenido (texto, imagen, lista, etc.)\n" .
        "- Clases CSS sugeridas\n" .
        "- Orden de aparicion\n\n" .
        "Tambien define:\n" .
        "- Titulo de la pagina (title)\n" .
        "- Descripcion meta\n" .
        "- Estructura semantica HTML completa\n\n" .
        "Responde UNICAMENTE con un objeto JSON valido con la siguiente estructura:\n" .
        '{
  "title": "Titulo de la pagina",
  "metaDescription": "Descripcion meta",
  "sections": [
    {
      "id": "hero",
      "type": "hero",
      "title": "Titulo del hero",
      "subtitle": "Subtitulo",
      "ctaText": "Texto del boton CTA",
      "ctaLink": "#",
      "background": "gradient o image",
      "content": "Contenido principal"
    }
  ],
  "designPreferences": {
    "colorScheme": "guindo",
    "typography": "moderna",
    "layout": "limpio y profesional"
  }
}' . "\n\n" .
        "No incluyas texto adicional antes o despues del JSON. Solo el JSON valido.";
}

// Construir prompt del constructor
function buildBuilderPrompt($structure, $slug, $menuParent, $menuItemName) {
    return
        "Eres el Constructor de Archivos de JDEconomy AI Work, un experto desarrollador frontend.\n\n" .
        "Tu tarea es generar los archivos reales (HTML, CSS, JS) para una pagina web basada en la estructura proporcionada.\n\n" .
        "ESTRUCTURA DE LA PAGINA (del Agente Arquitecto):\n" . json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n" .
        "DATOS:\n" .
        "- Slug: " . $slug . "\n" .
        "- Menu Padre: " . $menuParent . "\n" .
        "- Nombre del item: " . $menuItemName . "\n\n" .
        "REQUISITOS TECNICOS:\n" .
        "1. HTML semantico y accesible\n" .
        "2. CSS moderno con variables CSS\n" .
        "3. Soporte completo de modo claro/oscuro con data-theme\n" .
        "4. Diseno responsive desde 320px\n" .
        "5. Transiciones suaves, sombras sutiles, bordes redondeados\n" .
        "6. Tipografia: Plus Jakarta Sans o Inter\n" .
        "7. Accesibilidad: contrastes, focus visible, aria labels, roles semanticos\n" .
        "8. Menu superior integrado y responsive\n\n" .
        "GENERA:\n" .
        "1. HTML completo y semantico\n" .
        "2. CSS completo con variables CSS\n" .
        "3. JS minimo necesario\n\n" .
        "Responde UNICAMENTE con un objeto JSON valido:\n" .
        '{
  "html_content": "<section>...</section>",
  "css_content": ":root { ... }",
  "js_content": "// JavaScript minimo",
  "pageTitle": "Titulo de la pagina",
  "filePath": "' . $slug . '/index.html"
}' . "\n\n" .
        "No incluyas texto adicional antes o despues del JSON. Solo el JSON valido.";
}

// Construir prompt del supervisor
function buildSupervisorPrompt($pageData, $slug, $menuParent, $menuItemName) {
    return
        "Eres el Supervisor de Calidad de JDEconomy AI Work, un experto en UX/UI design, accesibilidad y rendimiento web.\n\n" .
        "Tu tarea es revisar y mejorar la calidad del diseno de la pagina generada.\n\n" .
        "DATOS DE LA PAGINA:\n" .
        "- Slug: " . $slug . "\n" .
        "- Menu Padre: " . $menuParent . "\n" .
        "- Nombre del item: " . $menuItemName . "\n\n" .
        "DATOS DE LA PAGINA GENERADA:\n" . json_encode($pageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n" .
        "REVISA Y MEJORA:\n" .
        "1. Calidad del diseno y coherencia visual\n" .
        "2. Tipografia y jerarquia de tamaños\n" .
        "3. Espaciado generoso y limpio\n" .
        "4. Experiencia de usuario\n" .
        "5. Accesibilidad (contraste WCAG, focus, aria labels)\n" .
        "6. Rendimiento\n" .
        "7. Modo claro/oscuro\n" .
        "8. Responsividad completa\n\n" .
        "Responde UNICAMENTE con un objeto JSON valido:\n" .
        '{
  "html_content": "<section>...</section>",
  "css_content": "[CSS mejorado]",
  "js_content": "[JS mejorado]",
  "improvements": ["lista de mejoras"],
  "accessibilityScore": 95,
  "performanceScore": 90,
  "pageTitle": "Titulo final"
}' . "\n\n" .
        "Responde UNICAMENTE con un objeto JSON valido con las mejoras aplicadas. No incluyas texto adicional antes o despues del JSON. Solo el JSON valido.";
}

// ========== OBTENER ÍTEMS DE MENÚ GENERADOS ==========
function getGeneratedMenuItemsList() {
    $itemsFile = GENERATED_DIR . '/menu_items.json';
    if (file_exists($itemsFile)) {
        return json_decode(file_get_contents($itemsFile), true) ?: [];
    }
    return [];
}

// ========== GESTIÓN DE PÁGINAS ==========
function handlePages($method) {
    if ($method === 'GET') {
        $pages = [];
        $genDir = GENERATED_DIR;
        if (is_dir($genDir)) {
            $slugs = scandir($genDir);
            foreach ($slugs as $slug) {
                if ($slug === '.' || $slug === '..') continue;
                $slugPath = $genDir . '/' . $slug;
                if (!is_dir($slugPath)) continue;
                $subslugs = scandir($slugPath);
                foreach ($subslugs as $subslug) {
                    if ($subslug === '.' || $subslug === '..') continue;
                    $subPath = $slugPath . '/' . $subslug;
                    if (!is_dir($subPath)) continue;
                    $indexFile = $subPath . '/index.html';
                    if (file_exists($indexFile)) {
                        $url = ($slug === 'sidebar')
                            ? PROJECT_BASE . '/' . $subslug . '/'
                            : PROJECT_BASE . '/' . $slug . '/' . $subslug . '/';
                        $pages[] = [
                            'slug' => $slug,
                            'subslug' => $subslug,
                            'url' => $url,
                            'file' => $indexFile
                        ];
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'pages' => $pages]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Metodo no permitido']);
    }
}

// ========== OBTENER MENÚS ==========
function handleMenus($method) {
    if ($method === 'GET') {
        require_once dirname(__DIR__) . '/config/db.php';
        require_once dirname(__DIR__) . '/config/functions.php';

        $menus = [];
        $all = getAllMenuItems($pdo);
        $mainMenus = array_filter($all, fn($m) => $m['parent_id'] === null);

        foreach ($mainMenus as $menu) {
            $children = array_values(array_filter($all, fn($m) => $m['parent_id'] == $menu['id']));
            $menus[] = [
                'id' => $menu['id'],
                'name' => $menu['name'],
                'slug' => $menu['slug'],
                'url' => $menu['url'],
                'icon' => $menu['icon'],
                'sort_order' => $menu['sort_order'],
                'children' => $children
            ];
        }

        echo json_encode(['success' => true, 'menus' => $menus]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Metodo no permitido']);
    }
}