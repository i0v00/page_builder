# JDEconomy AI Work

Sistema profesional de generación de páginas web mediante agentes de Cloudflare AI Workers.

## Estructura del Proyecto

```
jdeconomy/
├── .env                      # Variables de entorno (Cloudflare)
├── .env.example              # Ejemplo de variables de entorno
├── .htaccess                 # Reescritura de URLs (Apache/Laragon)
├── index.html                # Página base (redirige a /ai-work)
├── ai-work.html              # Panel de generación de páginas
├── api/
│   └── index.php             # API backend con 3 agentes de IA
├── assets/
│   ├── css/
│   │   └── style.css         # Estilos principales (paleta Guindo)
│   └── js/
│       └── app.js            # Lógica del panel y pipeline de agentes
├── generated/                # Páginas generadas (creadas automáticamente)
│   ├── [slug]/
│   │   └── [subslug]/
│   │       ├── index.html
│   │       ├── style.css
│   │       └── script.js
│   └── menu_items.json       # Ítems del menú de navegación
├── templates/                # Plantillas de páginas
└── README.md                 # Este archivo
```

## Rutas

| Ruta | Descripción |
|------|-------------|
| `/jdeconomy/` | Redirige al panel AI Work |
| `/jdeconomy/ai-work` | Panel de generación de páginas |
| `/jdeconomy/[slug]/[subslug]` | Página generada (ej: `/servicios/consultoria`) |

## Configuración Inicial

### 1. Configurar .env

Copia `.env.example` a `.env` y completa con tus credenciales de Cloudflare:

```
CLOUDFLARE_ACCOUNT_ID=tu_account_id_aqui
CLOUDFLARE_API_TOKEN=tu_api_token_aqui
CLOUDFLARE_AI_GATEWAY=opcional_si_usas_gateway
```

### 2. Obtener Credenciales de Cloudflare

1. Inicia sesión en [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Obtén tu **Account ID** de la barra lateral derecha
3. Crea un **API Token** con permisos para Workers AI:
   - Ve a My Profile > API Tokens > Create Token
   - Usa la plantilla "Cloudflare Workers AI" o crea un token personalizado con:
     - `Workers AI - Edit`
     - `Workers AI - Read`
4. Copia el token al archivo `.env`

### 3. Modelos Utilizados por Agente

| Agente | Modelo | Tarea |
|--------|--------|-------|
| Arquitecto | `@cf/meta/llama-3.1-70b-instruct` | Estructura y contenido semántico |
| Constructor | `@cf/qwen/qwen2.5-coder-32b-instruct` | Código HTML/CSS/JS limpio |
| Supervisor | `@cf/meta/llama-3.1-70b-instruct` | Revisión y mejora de calidad |

## Sistema de 3 Agentes

### Agente 1 – Arquitecto de Página
Recibe el prompt del usuario y define:
- Estructura semántica HTML
- Secciones: Hero, Descripción, Features, Testimonios, CTA, Footer
- Contenido textual completo
- Preferencias de diseño

### Agente 2 – Constructor de Archivos
Recibe la estructura y genera:
- Archivo HTML completo y semántico
- CSS con variables CSS (modo claro/oscuro)
- JavaScript mínimo necesario
- Publica en `/[slug]/[subslug]/`

### Agente 3 – Supervisor / Mejorador
Revisa y mejora:
- Calidad de diseño y coherencia visual
- Tipografía, espaciado, experiencia de usuario
- Accesibilidad (WCAG, focus, aria labels)
- Rendimiento
- Puede solicitar regeneración de partes específicas

## Panel /ai-work

El panel incluye:
- Formulario con campos: Slug, Subslug, Prompt (textarea 10 filas)
- Selector de posición en menú (Inicio, Servicios, Productos, Nosotros, Contacto, Personalizado)
- Botón de generación con estado de carga
- Pipeline visual de agentes (Arquitecto → Constructor → Supervisor)
- Vista previa del resultado
- Mensajes de error claros

## Diseño Visual

### Paleta de Colores Guindo

**Modo Claro:**
- Primario: #6B1E2A
- Fondo: #FAF7F7
- Texto principal: #1A1A1A

**Modo Oscuro:**
- Primario: #C45C6A
- Fondo: #121212
- Texto principal: #F5F5F5

### Características
- Tipografía: Plus Jakarta Sans / Inter
- Soporte completo claro/oscuro con toggle
- Responsive desde 320px
- Menú hamburguesa en mobile
- Transiciones suaves y sombras elegantes

## Instrucciones para Antigravity

### Paso 1: Desplegar en Servidor

1. Sube todo el contenido de `/jdeconomy/` a tu servidor web
2. Asegúrate de que Apache tenga `mod_rewrite` habilitado
3. El archivo `.htaccess` maneja las URLs limpias automáticamente

### Paso 2: Configurar Variables de Entorno

1. Renombra `.env.example` a `.env`
2. Completa `CLOUDFLARE_ACCOUNT_ID` y `CLOUDFLARE_API_TOKEN`
3. Asegúrate de que PHP pueda leer el archivo `.env` (permisos de lectura)

### Paso 3: Verificar Instalación

1. Accede a `http://tu-dominio/jdeconomy/`
2. Debería redirigir al panel `/jdeconomy/ai-work`
3. Completa el formulario y genera una página de prueba
4. Verifica que la página se genere en `/jdeconomy/[slug]/[subslug]/`

### Paso 4: Personalización

- **Colores:** Edita `assets/css/style.css` para cambiar la paleta
- **Modelos:** Edita `api/index.php` para cambiar los modelos de Cloudflare AI
- **Tipografía:** Cambia la fuente de Google Fonts en los archivos HTML

## Requisitos Técnicos

- PHP 7.4 o superior
- Apache con mod_rewrite
- Extensión cURL de PHP habilitada
- Acceso a internet para llamar a Cloudflare AI Workers API

## Soporte

Para soporte o reportar problemas, contacta al equipo de desarrollo de JDEconomy.