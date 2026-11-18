-- JDEconomy Academia - Base de Datos
-- Crear base de datos
CREATE DATABASE IF NOT EXISTS jdeconomy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jdeconomy;

-- Tabla: menús y submenús
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    parent_id INT DEFAULT NULL,
    url VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_parent (parent_id),
    INDEX idx_slug (slug),
    INDEX idx_sort (sort_order)
);

-- Tabla: cursos
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subtitle VARCHAR(300) DEFAULT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    menu_id INT DEFAULT NULL,
    description TEXT,
    short_description VARCHAR(500),
    duration VARCHAR(50) DEFAULT NULL,
    level VARCHAR(50) DEFAULT 'Principiante',
    price DECIMAL(10,2) DEFAULT 0.00,
    image_url VARCHAR(500) DEFAULT NULL,
    is_published TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES menu_items(id) ON DELETE SET NULL,
    INDEX idx_menu (menu_id),
    INDEX idx_slug (slug),
    INDEX idx_sort (sort_order)
);

-- Tabla: páginas generadas por IA
CREATE TABLE IF NOT EXISTS generated_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL,
    subslug VARCHAR(100) NOT NULL,
    title VARCHAR(200) NOT NULL,
    menu_slug VARCHAR(100) DEFAULT NULL,
    menu_item_name VARCHAR(100) DEFAULT NULL,
    file_path VARCHAR(500) DEFAULT NULL,
    prompt TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slug_subslug (slug, subslug),
    INDEX idx_slug (slug),
    INDEX idx_subslug (subslug)
);

-- Insertar menús principales
INSERT INTO menu_items (name, slug, url, icon, sort_order) VALUES
('Inicio', 'inicio', '/jdeconomy/', 'home', 1),
('Cursos', 'cursos', '/jdeconomy/cursos/', 'book', 2),
('Macroeconomía', 'macroeconomia', '/jdeconomy/macroeconomia/', 'chart-line', 3),
('Ofimática', 'ofimatica', '/jdeconomy/ofimatica/', 'laptop-code', 4),
('Nosotros', 'nosotros', '/jdeconomy/nosotros/', 'users', 5),
('Contacto', 'contacto', '/jdeconomy/contacto/', 'mail', 6);

-- Insertar submenús de Macroeconomía (2 cursos)
INSERT INTO menu_items (name, slug, parent_id, url, icon, sort_order) VALUES
('Introducción a la Macroeconomía', 'introduccion-macroeconomia', 3, '/jdeconomy/macroeconomia/introduccion/', 'play', 1),
('Política Monetaria y Fiscal', 'politica-monetaria-fiscal', 3, '/jdeconomy/macroeconomia/politica-monetaria/', 'play', 2);

-- Insertar submenús de Ofimática (5 cursos)
INSERT INTO menu_items (name, slug, parent_id, url, icon, sort_order) VALUES
('Excel Avanzado', 'excel-avanzado', 4, '/jdeconomy/ofimatica/excel-avanzado/', 'play', 1),
('Word Profesional', 'word-profesional', 4, '/jdeconomy/ofimatica/word-profesional/', 'play', 2),
('PowerPoint Empresarial', 'powerpoint-empresarial', 4, '/jdeconomy/ofimatica/powerpoint-empresarial/', 'play', 3),
('Access Bases de Datos', 'access-bases-datos', 4, '/jdeconomy/ofimatica/access-bases-datos/', 'play', 4),
('Outlook y Correo Corporativo', 'outlook-correo', 4, '/jdeconomy/ofimatica/outlook-correo/', 'play', 5);

-- Insertar cursos
INSERT INTO courses (title, subtitle, slug, menu_id, description, short_description, duration, level, price, sort_order) VALUES
-- Macroeconomía cursos
('Fundamentos de Macroeconomía', 'Entiende los conceptos básicos de la economía a gran escala', 'fundamentos-macroeconomia', 3, 'Curso completo que cubre los principios fundamentales de la macroeconomía: PIB, inflación, desempleo, y política económica.', 'Aprende los conceptos básicos de macroeconomía', '40 horas', 'Principiante', 99.00, 1),
('Política Monetaria y Fiscal', 'Análisis de políticas económicas del Estado', 'politica-monetaria-fiscal', 3, 'Estudia las herramientas de política monetaria y fiscal que utilizan los gobiernos y bancos centrales para estabilizar la economía.', 'Políticas económicas del Estado', '60 horas', 'Intermedio', 149.00, 2),

-- Ofimática cursos
('Excel Avanzado para Negocios', 'Domina Excel a nivel profesional', 'excel-avanzado', 4, 'Curso avanzado de Microsoft Excel enfocado en análisis de datos, macros, tablas dinámicas y modelado financiero.', 'Excel avanzado para el mundo empresarial', '50 horas', 'Avanzado', 129.00, 1),
('Word Profesional', 'Creación de documentos empresariales', 'word-profesional', 4, 'Aprende a crear documentos profesionales, reportes, cartas formales y materiales de presentación con Word.', 'Documentos empresariales con Word', '30 horas', 'Principiante', 79.00, 2),
('PowerPoint Empresarial', 'Presentaciones de impacto', 'powerpoint-empresarial', 4, 'Diseña presentaciones profesionales y persuasivas para el entorno corporativo con PowerPoint.', 'Presentaciones corporativas con impacto', '35 horas', 'Intermedio', 89.00, 3),
('Access: Bases de Datos', 'Gestión de bases de datos con Access', 'access-bases-datos', 4, 'Curso introductorio sobre creación y gestión de bases de datos relacionales con Microsoft Access.', 'Bases de datos con Microsoft Access', '45 horas', 'Intermedio', 109.00, 4),
('Outlook y Correo Corporativo', 'Gestión de correo y calendario empresarial', 'outlook-correo', 4, 'Optimiza tu comunicación corporativa con Outlook: correo, calendario, contactos y tareas.', 'Comunicación corporativa eficiente', '25 horas', 'Principiante', 69.00, 5);