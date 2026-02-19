# Education Resources Manager

Plugin de WordPress para gestionar recursos educativos (cursos, tutoriales, ebooks, videos).

## Requisitos

- **WordPress:** 6.0 o superior
- **PHP:** 7.4 o superior

## Instalación

1. Clona o descarga el plugin en `wp-content/plugins/education-resources-manager`
2. Activa el plugin desde el panel de administración de WordPress
3. La tabla de tracking se creará automáticamente al activar

## Uso

### Shortcode

Añade el shortcode en cualquier página o entrada para mostrar el listado de recursos:

```
[recursos_educativos]
```

**Atributos opcionales:**

- `per_page` - Número de recursos por página (default: 9)

Ejemplo:

```
[recursos_educativos per_page="12"]
```

### Características

- **Custom Post Type:** Recurso Educativo con campos personalizados (tipo, nivel, duración, URL, instructor, precio, estado)
- **Taxonomías:** Categorías de recursos (jerárquica) y etiquetas de habilidades (no jerárquica)
- **Filtros dinámicos:** Por tipo, nivel, categoría y búsqueda por texto (AJAX)
- **Tracking:** Registro de visualizaciones y descargas
- **Panel de administración:** Dashboard con estadísticas y gráficos

### REST API

Endpoints disponibles bajo `/wp-json/erm/v1/`:

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/resources` | GET | Listar recursos con filtros |
| `/resources/{id}` | GET | Obtener recurso específico |
| `/resources/{id}/track` | POST | Registrar visualización |
| `/stats` | GET | Estadísticas (requiere autenticación) |

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
│   └── class-erm-shortcode.php
├── admin/
├── public/
├── database/
├── docs/
└── README.md
```

## Documentación

Ver carpeta `/docs` para documentación detallada de arquitectura, base de datos y API.

## Licencia

GPL v2 or later
