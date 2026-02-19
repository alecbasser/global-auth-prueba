# Education Resources Manager

Plugin de WordPress para gestionar recursos educativos (cursos, tutoriales, ebooks, videos).

## Versión: 2.0.0

## Requisitos
- WordPress: 6.0 o superior
- PHP: 7.4 o superior

## Instalación
1. Clona o descarga el plugin en `wp-content/plugins/education-resources-manager`
2. Activa el plugin desde el panel de administración de WordPress
3. La tabla de tracking se creará automáticamente al activar

## Uso

### Shortcode
`[recursos_educativos]` - Muestra listado de recursos con filtros dinámicos (AJAX)
- Atributo `per_page`: Número de recursos por página (default: 9)

### Características principales
- **Custom Post Type**: Recurso Educativo con campos personalizados (tipo, nivel, duración, URL, instructor, precio, estado)
- **Taxonomías**: Categorías de recursos (jerárquica) y etiquetas de habilidades (no jerárquica)
- **Filtros dinámicos**: Por tipo, nivel, categoría y búsqueda por texto (AJAX sin recarga)
- **Tracking**: Registro automático de visualizaciones
- **Panel de administración**: Dashboard con estadísticas y gráficos
- **Página de configuración**: Permite ajustar:
  - Máximo de caracteres del extracto en tarjetas de recursos
  - Tipo de visualización del recurso individual (2 opciones):
    - **Default**: Imagen arriba, datos y sinopsis debajo
    - **Side-by-side**: Imagen a la izquierda con datos del recurso a la derecha, sinopsis a ancho completo debajo
- **Panel de estado en editor de bloques**: Panel personalizado con 3 estados (Borrador, Publicado, Archivado) sincronizado bidireccionalmente con el meta box
- **Vista de recurso individual**: Badges, autor, precio formateado ($ con separador de miles), botón CTA, compartir, favoritos (localStorage)
- **Precio formateado**: Separador de miles automático tanto en vista individual como en tarjetas del listado

### REST API
| Endpoint | Método | Descripción | Autenticación |
|---|---|---|---|
| /wp-json/erm/v1/resources | GET | Listar recursos con filtros | Pública |
| /wp-json/erm/v1/resources/{id} | GET | Obtener recurso específico | Pública |
| /wp-json/erm/v1/resources/{id}/track | POST | Registrar visualización | Nonce |
| /wp-json/erm/v1/stats | GET | Estadísticas (admin) | Cookie + edit_posts |

## Estructura
```
education-resources-manager/
├── education-resources-manager.php
├── includes/
│   ├── class-erm-activator.php
│   ├── class-erm-deactivator.php
│   ├── class-erm-post-type.php
│   ├── class-erm-taxonomy.php
│   ├── class-erm-database.php
│   ├── class-erm-admin.php
│   ├── class-erm-rest-api.php
│   ├── class-erm-shortcode.php
│   ├── class-erm-single-template.php
│   └── class-erm-uninstall.php
├── admin/
│   ├── css/admin-styles.css
│   ├── js/admin-scripts.js
│   ├── js/erm-block-editor.js
│   └── views/
│       ├── admin-page.php
│       └── settings-page.php
├── public/
│   ├── css/
│   │   ├── public-styles.css
│   │   └── erm-single.css
│   └── js/
│       ├── public-scripts.js
│       └── erm-single.js
├── docs/
│   ├── ARCHITECTURE.md
│   ├── DATABASE.md
│   └── API.md
└── database/
    └── schema.sql
```

## Documentación
Ver carpeta `/docs` para documentación detallada de arquitectura, base de datos y API.

## Licencia
GPL v2 or later
