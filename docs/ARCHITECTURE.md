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
│   ├── class-erm-admin.php         # Dashboard admin
│   ├── class-erm-rest-api.php      # Endpoints REST
│   └── class-erm-shortcode.php     # [recursos_educativos]
├── admin/
│   ├── css/admin-styles.css
│   ├── js/admin-scripts.js
│   └── views/admin-page.php
├── public/
│   ├── css/public-styles.css
│   └── js/public-scripts.js
├── database/schema.sql
└── docs/
```

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
**Decisión:** Cargar CSS/JS solo en páginas con shortcode

**Implementación:** `has_shortcode($post->post_content, 'recursos_educativos')` antes de wp_enqueue_script/style

---

## Seguridad

- **Sanitización:** sanitize_text_field, esc_url_raw, absint en todos los inputs
- **Nonces:** erm_save_resource_meta (meta box), erm_track_{id} (tracking), wp_rest (REST)
- **Capabilities:** current_user_can('edit_post'), current_user_can('edit_posts') para stats
- **Prepared statements:** $wpdb->prepare() en todas las queries
- **Escaping:** esc_html, esc_attr, esc_url en outputs
