# Documentación API REST - Education Resources Manager

## Base URL
```
/wp-json/erm/v1
```

---

## Resumen de Endpoints

| Endpoint | Método | Descripción | Autenticación |
|----------|--------|-------------|---------------|
| `/resources` | GET | Listar recursos con filtros | Pública |
| `/resources/{id}` | GET | Obtener recurso específico | Pública |
| `/resources/{id}/track` | POST | Registrar visualización o descarga | Nonce |
| `/stats` | GET | Estadísticas (admin) | Cookie + edit_posts |

---

## 1. GET /resources

Lista recursos con filtros opcionales.

**Autenticación:** Pública (no requiere autenticación).

**Parámetros:**
| Parámetro | Tipo   | Default | Descripción |
|-----------|--------|---------|-------------|
| page      | int    | 1       | Número de página |
| per_page  | int    | 10      | Recursos por página (máx. 50) |
| type      | string | -       | course, tutorial, ebook, video (valor inválido = 0 resultados) |
| level     | string | -       | beginner, intermediate, advanced (valor inválido = 0 resultados) |
| category  | int/string | - | ID o slug de categoría (valor inválido = 0 resultados) |
| search    | string | -       | Búsqueda por título |

**Ejemplo:**
```
GET /wp-json/erm/v1/resources?type=course&level=beginner&per_page=9
```

**Respuesta (200):** Array de objetos recurso.

**Headers de respuesta:**
- `X-WP-Total`: Total de recursos que coinciden con los filtros
- `X-WP-TotalPages`: Número total de páginas

**Objeto recurso:**
```json
{
  "id": 123,
  "title": "Título",
  "excerpt": "Resumen...",
  "permalink": "https://...",
  "thumbnail": "https://...",
  "type": "course",
  "level": "beginner",
  "duration": 60,
  "url": "https://...",
  "instructor": "Nombre",
  "price": "Gratuito",
  "categories": [],
  "skills": [],
  "track_nonce": "abc123"
}
```

---

## 2. GET /resources/{id}

Obtiene un recurso específico por ID.

**Autenticación:** Pública (no requiere autenticación).

**Parámetros de ruta:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| id        | int  | ID del recurso (post_id) |

**Respuesta (200):** Objeto recurso completo con `content` incluido (contenido procesado con filtros de WordPress).

**Objeto recurso:**
```json
{
  "id": 123,
  "title": "Título",
  "content": "<p>Contenido HTML...</p>",
  "excerpt": "Resumen...",
  "permalink": "https://...",
  "thumbnail": "https://...",
  "type": "course",
  "level": "beginner",
  "duration": 60,
  "url": "https://...",
  "instructor": "Nombre",
  "price": "Gratuito",
  "categories": [],
  "skills": []
}
```

**Errores:**
- **404**: Recurso no encontrado o no está publicado.

---

## 3. POST /resources/{id}/track

Registra una visualización o descarga del recurso.

**Autenticación:** Requiere nonce específico del recurso. El nonce se incluye en la respuesta de GET /resources y GET /resources/{id}. Se verifica con `wp_verify_nonce($nonce, 'erm_track_' . $id)`.

**Parámetros de ruta:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| id        | int  | ID del recurso |

**Body (JSON):**
| Campo  | Tipo   | Requerido | Descripción |
|--------|--------|-----------|-------------|
| action | string | No        | "view" o "download". Default: "view" |
| nonce  | string | Sí        | Nonce generado con `wp_create_nonce('erm_track_' . $id)` |

**Headers:**
- `Content-Type: application/json`
- `X-WP-Nonce`: Nonce de wp_rest (opcional para usuarios no autenticados; el nonce del body es el crítico)

**Ejemplo:**
```json
{
  "action": "view",
  "nonce": "nonce_from_track_nonce_field"
}
```

**Respuesta (200):**
```json
{
  "success": true,
  "message": "Acción registrada correctamente."
}
```

**Errores:**
- **403**: `invalid_nonce` - Verificación de seguridad fallida.
- **404**: Recurso no encontrado o no publicado.
- **500**: Error al registrar la acción.

---

## 4. GET /stats

Devuelve estadísticas agregadas para el panel de administración.

**Autenticación:** Requiere usuario con capacidad `edit_posts`. Soporta dos métodos:
1. **Nonce REST (X-WP-Nonce)**: Para peticiones AJAX desde el admin.
2. **Cookie de sesión**: Fallback para acceso directo por URL en el navegador (cuando el usuario está logueado). Se valida con `wp_validate_auth_cookie()` y `user_can($user_id, 'edit_posts')`.

**Parámetros:** Ninguno.

**Respuesta (200):**
```json
{
  "by_type": [
    { "type": "course", "count": "10" },
    { "type": "tutorial", "count": "5" }
  ],
  "top_viewed": [
    { "id": 123, "title": "...", "view_count": 45 }
  ],
  "by_month": [
    { "month": "2025-01", "count": "3" }
  ]
}
```

**Campos:**
- `by_type`: Conteo de recursos por tipo (course, tutorial, ebook, video).
- `top_viewed`: Top 5 recursos más vistos (id, title, view_count).
- `by_month`: Recursos publicados por mes (últimos 6 meses).

**Errores:**
- **404**: Usuario no autenticado o sin permisos.

---

## Códigos de Error

| Código | Significado        |
|--------|--------------------|
| 200    | OK                 |
| 403    | Invalid nonce      |
| 404    | Not found         |
| 500    | Server error      |

---

## Ejemplo JavaScript

```javascript
// Listar recursos (pública)
const res = await fetch('/wp-json/erm/v1/resources?type=course');
const resources = await res.json();
const total = res.headers.get('X-WP-Total');

// Registrar tracking (requiere nonce del recurso)
await fetch(`/wp-json/erm/v1/resources/${id}/track`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': ermData.nonce  // opcional si no hay sesión
  },
  body: JSON.stringify({ action: 'view', nonce: resource.track_nonce })
});

// Obtener estadísticas (requiere edit_posts)
const statsRes = await fetch('/wp-json/erm/v1/stats', {
  headers: { 'X-WP-Nonce': ermData.nonce }
});
const stats = await statsRes.json();
```
