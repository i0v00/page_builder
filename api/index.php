<?php
// JDEconomy AI Work - Configuración de Cloudflare AI Workers
// Lee las variables de entorno desde .env

// Cargar variables de entorno desde .env si existe
$envFile = __DIR__ . '/../../.env';
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
define('GENERATED_DIR', __DIR__ . '/../../generated');

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

// Llamar a Cloudflare Workers AI
function callCloudflareAI($model, $prompt, $maxTokens = 4000) {
    validateCloudflareConfig();

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
    $response = preg_replace('/^```(?:json)?\s*/i', '', $response);
    $response = preg_replace('/\s*```\s*$/i', '', $response);
    $response = trim($response);

    // Intentar decodificar directamente
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $data;
    }

    // Buscar el primer { y el ultimo } para extraer solo el objeto JSON
    $start = strpos($response, '{');
    $end = strrpos($response, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $json = substr($response, $start, $end - $start + 1);
        $data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
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

// Generar menú HTML para páginas generadas
function generateMenuHTML($menuItems, $currentSlug = '', $currentSubslug = '') {
    $menuHtml = '<nav class="nav-menu">';
    foreach ($menuItems as $item) {
        $isActive = ($item['slug'] === $currentSlug && $item['subslug'] === $currentSubslug);
        $class = $isActive ? 'class="active"' : '';
        $menuHtml .= sprintf(
            '<a href="/jdeconomy/%s/%s/" %s>%s</a>',
            htmlspecialchars($item['slug']),
            htmlspecialchars($item['subslug']),
            $class,
            htmlspecialchars($item['label'])
        );
    }
    $menuHtml .= '</nav>';
    return $menuHtml;
}

// Generar HTML completo de una página
function generatePageHTML($pageData, $menuItems = []) {
    $slug = htmlspecialchars($pageData['slug'] ?? '');
    $menuParent = $pageData['menuParent'] ?? 'sidebar';
    $menuItemName = $pageData['menuItemName'] ?? '';
    $title = htmlspecialchars($pageData['title'] ?? 'Página');
    
    // Determinar el slug del menú para la URL y resaltado
    $menuSlug = $menuParent === 'sidebar' ? '' : $menuParent;
    $currentSlug = $menuSlug;
    $currentSubslug = $slug;
    
    $menuHtml = generateMenuHTML($menuItems, $currentSlug, $currentSubslug);
    $bodyContent = $pageData['html_content'] ?? '';
    $cssContent = $pageData['css_content'] ?? '';
    $jsContent = $pageData['js_content'] ?? '';

    $htmlTemplate = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>%s - JDEconomy</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
%s
    </style>
</head>
<body>
    <header class="header generated-menu">
        <div class="header-inner">
            <div class="logo">
                <div class="logo-mark">JD</div>
                <div class="logo-text">JDEconomy</div>
            </div>
            %s
            <nav class="nav-actions">
                <button class="theme-toggle" aria-label="Cambiar tema">
                    <span class="theme-icon">☀️</span>
                </button>
            </nav>
        </div>
    </header>

    <main>
%s
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">JDEconomy</div>
            <div class="footer-text">Generado con JDEconomy AI Work &copy; 2026</div>
        </div>
    </footer>

    <script>
%s
        // Inicializar tema
        (function() {
            const btn = document.querySelector('.theme-toggle');
            const icon = btn.querySelector('.theme-icon');
            const saved = localStorage.getItem('theme') || 'light';
            if (saved === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                icon.textContent = '🌙';
            }
            btn.addEventListener('click', function() {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark');
                localStorage.setItem('theme', isDark ? 'light' : 'dark');
                icon.textContent = isDark ? '☀️' : '🌙';
            });
        })();
    </script>
</body>
</html>
HTML;

    $html = sprintf($htmlTemplate, $title, $cssContent, $menuHtml, $bodyContent, $jsContent);

    return $html;
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
    $structure = $input['structure'] ?? [];
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
        "1. HTML semantico y accesible (etiquetas section, header, nav, main, footer, article, etc.)\n" .
        "2. CSS moderno con variables CSS (paleta de colores Guindo: #6B1E2A primario, #FAF7F7 fondo claro, #121212 fondo oscuro)\n" .
        "3. Soporte completo de modo claro/oscuro con data-theme\n" .
        "4. Diseno responsive desde 320px hasta pantallas grandes\n" .
        "5. Transiciones suaves, sombras sutiles, bordes redondeados moderados\n" .
        "6. Tipografia: Plus Jakarta Sans o Inter\n" .
        "7. Accesibilidad: contrastes, focus visible, aria labels, roles semanticos\n" .
        "8. Menu superior integrado y responsive (hamburguesa en mobile)\n\n" .
        "GENERA:\n" .
        "1. HTML completo y semantico para el contenido de la pagina (solo el body content, sin <html><head><body>)\n" .
        "2. CSS completo con variables CSS para modo claro/oscuro\n" .
        "3. JS minimo necesario (menu movil, toggle tema, interacciones)\n\n" .
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
        ], getMenuItems());

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
    $pageData = $input['pageData'] ?? [];
    $slug = $input['slug'] ?? 'pagina';
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
        "1. Calidad del diseno y coherencia visual\n" .
        "2. Tipografia y jerarquia de tamaños\n" .
        "3. Espaciado generoso y limpio\n" .
        "4. Experiencia de usuario\n" .
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
  "accessibilityScore": 95,
  "performanceScore": 90,
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
        ], getMenuItems());

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

// ========== OBTENER ÍTEMS DE MENÚ ==========
function getMenuItems() {
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
                        $pages[] = [
                            'slug' => $slug,
                            'subslug' => $subslug,
                            'url' => PROJECT_BASE . '/' . $slug . '/' . $subslug . '/',
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
        require_once __DIR__ . '/../config/db.php';
        require_once __DIR__ . '/../config/functions.php';

        $menus = [];
        $mainMenus = getMenuItems($pdo);

        foreach ($mainMenus as $menu) {
            $menuItem = [
                'id' => $menu['id'],
                'name' => $menu['name'],
                'slug' => $menu['slug'],
                'url' => $menu['url'],
                'icon' => $menu['icon'],
                'sort_order' => $menu['sort_order'],
                'children' => []
            ];

            $children = array_filter($menuItems ?? [], fn($m) => $m['parent_id'] == $menu['id']);
            foreach ($children as $child) {
                $menuItem['children'][] = [
                    'id' => $child['id'],
                    'name' => $child['name'],
                    'slug' => $child['slug'],
                    'url' => $child['url'],
                    'icon' => $child['icon'],
                    'sort_order' => $child['sort_order'],
                    'parent_id' => $child['parent_id']
                ];
            }

            $menus[] = $menuItem;
        }

        echo json_encode(['success' => true, 'menus' => $menus]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Metodo no permitido']);
    }
}