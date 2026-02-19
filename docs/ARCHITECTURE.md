# Arquitectura del Sistema - Education Resources Manager

## Visión General

### Objetivo
Sistema de gestión de recursos educativos integrado en WordPress que permite crear, categorizar y mostrar cursos, tutoriales, ebooks y videos, con tracking de visualizaciones y panel de estadísticas.

### Alcance
- Custom Post Type para recursos educativos
- Taxonomías para categorización
- Tabla personalizada para tracking
- REST API para frontend dinámico
- Shortcode con filtros AJAX
- Panel de administración con estadísticas
- Página de configuración con opciones de layout
- Vista individual de recurso con dos diseños
- Panel de estado personalizado en editor de bloques
- Desinstalación completa con confirmación

---

## Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                      WordPress Core                          │
├─────────────────────────────────────────────────────────────┤
│  ┌───────────────────────────────────────────────────────┐  │
│  │         Education Resources Manager Plugin             │  │
│  │                                                         │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐  │  │
│  │  │   Backend    │  │   Frontend   │  │  REST API   │  │  │
│  │  │              │  │              │  │             │  │  │
│  │  │ - CPT        │  │ - Shortcode  │  │ - /resources│  │  │
│  │  │ - Taxonomies │  │ - AJAX       │  │ - /track    │  │  │
│  │  │ - Admin UI   │  │ - Filtros    │  │ - /stats    │  │  │
│  │  │ - Settings   │  │ - Single     │  │             │  │  │
│  │  │ - Block Edit │  │   Template   │  │             │  │  │
│  │  └──────────────┘  └──────────────┘  └─────────────┘  │  │
│  │                                                         │  │
│  │  ┌────────────────────────────────────────────────┐   │  │
│  │  │          Database Layer                        │   │  │
│  │  │  - erm_resource_tracking (custom table)        │   │  │
│  │  │  - wp_posts + wp_postmeta                      │   │  │
│  │  │  - wp_terms + wp_term_relationships            │   │  │
│  │  └────────────────────────────────────────────────┘   │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## Estructura de Archivos

```
education-resources-manager/
├── education-resources-manager.php   # Bootstrap, hooks de activación
├── includes/
│   ├── class-erm-activator.php      # Creación de tablas
│   ├── class-erm-deactivator.php    # Limpieza al desactivar
│   ├── class-erm-post-type.php      # CPT + meta boxes
│   ├── class-erm-taxonomy.php       # Categorías y habilidades
│   ├── class-erm-database.php       # CRUD tracking
│   ├── class-erm-admin.php          # Dashboard admin, configuración
│   ├── class-erm-rest-api.php       # Endpoints REST
│   ├── class-erm-shortcode.php      # [recursos_educativos]
│   ├── class-erm-single-template.php # Vista individual de recurso
│   └── class-erm-uninstall.php       # Desinstalación completa
├── admin/
│   ├── css/admin-styles.css
│   ├── js/admin-scripts.js
│   ├── js/erm-block-editor.js       # Panel de estado en Gutenberg
│   └── views/
│       ├── admin-page.php           # Dashboard
│       └── settings-page.php        # Página de configuración
├── public/
│   ├── css/
│   │   ├── public-styles.css
│   │   └── erm-single.css           # Estilos vista individual
│   └── js/
│       ├── public-scripts.js
│       └── erm-single.js            # Compartir, favoritos (localStorage)
├── database/schema.sql
└── docs/
```

---

## Clases Principales

### ERM_Single_Template
Gestiona la vista individual de cada recurso educativo. Inyecta metadatos estilizados (badges, autor, precio, CTA) en el contenido del tema respetando header y footer.

**Responsabilidades:**
- Filtro `the_content` para añadir bloque de metadatos del recurso
- Clase CSS `erm-single-page--side-by-side` según configuración
- Dos layouts configurables:
  - **Default**: Imagen arriba, badges/autor/precio debajo, sinopsis a continuación
  - **Side-by-side**: Imagen a la izquierda, datos del recurso a la derecha, sinopsis a ancho completo debajo
- Encolado de `erm-single.css` y `erm-single.js` solo en páginas de recurso individual

### ERM_Uninstall
Maneja la desinstalación completa del plugin con confirmación previa.

**Responsabilidades:**
- Enlace "Desinstalar" (rojo) en la lista de plugins
- Página de confirmación con advertencia y conteo de recursos afectados
- Borrado de todos los recursos, taxonomías, tabla de tracking y opciones
- Desactivación del plugin tras la limpieza
- Requiere `manage_options` y nonce de verificación

---

## Página de Configuración

Ubicada en **Recursos Educativos → Configuración**, permite ajustar:

| Opción | Clave | Descripción |
|--------|-------|-------------|
| Máximo de caracteres en extracto | `erm_excerpt_max_chars` | Límite para el extracto en tarjetas del listado (0 = sin límite). Default: 150 |
| Layout del recurso individual | `erm_single_layout` | `default` o `side-by-side` |

---

## Panel de Estado en Editor de Bloques

El plugin reemplaza el panel de estado de publicación por defecto con un panel simplificado para el CPT `erm_resource`:

- **3 estados**: Borrador, Publicado, Archivado
- **Sincronización bidireccional** con el select del meta box "Detalles del Recurso"
- El estado "Archivado" se registra como post_status personalizado `archived`
- Implementado en `admin/js/erm-block-editor.js` mediante `PluginDocumentSettingPanel`

---

## Tipos de Layout (Vista Individual)

### Default
- Imagen destacada arriba
- Badges (tipo, nivel, duración), autor, precio y botón CTA debajo
- Sinopsis del recurso a continuación

### Side-by-side
- Contenedor flex: imagen a la izquierda, bloque de datos a la derecha
- Badges, autor, precio y CTA en la columna derecha
- Sinopsis a ancho completo debajo del header

---

## Flujo de Datos

### Creación de Recurso
```
Admin → Editar post erm_resource → Meta box → save_post → wp_postmeta
```

### Visualización con Filtros (AJAX)
```
Usuario cambia filtro → JS fetch() → GET /erm/v1/resources?params
→ ERM_REST_API::get_resources() → WP_Query → JSON response
→ JS actualiza DOM con tarjetas
```

### Tracking de Visualización
```
Usuario clic "Ver recurso" → JS fetch() → POST /erm/v1/resources/{id}/track
→ Verificación nonce → ERM_Database::record_action()
→ INSERT en erm_resource_tracking → Success
```

---

## Decisiones Técnicas

### 1. Custom Post Type vs tabla personalizada para recursos
**Decisión:** Usar CPT (erm_resource)

**Razones:**
- Integración nativa con editor de WordPress
- Reutilización de wp_postmeta para campos personalizados
- Soporte de taxonomías nativo
- REST API y Gutenberg compatibles con show_in_rest

### 2. Tabla personalizada para tracking
**Decisión:** Tabla `{prefix}_erm_resource_tracking`

**Razones:**
- Alto volumen de registros (cada vista = 1 fila)
- Evitar saturar wp_postmeta
- Queries optimizadas con índices específicos
- Mejor rendimiento en estadísticas agregadas

### 3. REST API vs Admin AJAX
**Decisión:** REST API para el frontend

**Razones:**
- Estándar de WordPress
- Reutilizable desde cualquier cliente
- Nonce de wp_rest para autenticación
- Paginación vía headers X-WP-Total, X-WP-TotalPages

### 4. Enqueue condicional de assets
**Decisión:** Cargar CSS/JS solo en páginas con shortcode o vista individual

**Implementación:** `has_shortcode()` para listado; `is_singular(ERM_Post_Type::POST_TYPE)` para vista individual

---

## Seguridad

- **Sanitización:** sanitize_text_field, esc_url_raw, absint en todos los inputs
- **Nonces:** erm_save_resource_meta (meta box), erm_track_{id} (tracking), wp_rest (REST), erm_desinstalar (uninstall)
- **Capabilities:** current_user_can('edit_post'), current_user_can('edit_posts') para stats, current_user_can('manage_options') para configuración y desinstalación
- **Prepared statements:** $wpdb->prepare() en todas las queries
- **Escaping:** esc_html, esc_attr, esc_url en outputs
