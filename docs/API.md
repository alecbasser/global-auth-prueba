# Documentación API REST - Education Resources Manager

## Base URL
```
/wp-json/erm/v1
```

---

## Endpoints

### 1. GET /resources
Lista recursos con filtros.

**Parámetros:**
| Parámetro | Tipo   | Default | Descripción                    |
|-----------|--------|---------|--------------------------------|
| page      | int    | 1       | Página                         |
| per_page  | int    | 10      | Por página (máx. 50)          |
| type      | string | -       | course, tutorial, ebook, video |
| level     | string | -       | beginner, intermediate, advanced |
| category  | int    | -       | ID de categoría                |
| search    | string | -       | Búsqueda por título            |

**Ejemplo:**
```
GET /wp-json/erm/v1/resources?type=course&level=beginner&per_page=9
```

**Respuesta (200):** Array de recursos. Headers: `X-WP-Total`, `X-WP-TotalPages`

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

### 2. GET /resources/{id}
Obtiene un recurso específico.

**Respuesta (200):** Objeto recurso con `content` incluido.

**Errores:** 404 si no existe o no está publicado.

---

### 3. POST /resources/{id}/track
Registra visualización o descarga.

**Body (JSON):**
```json
{
  "action": "view",
  "nonce": "nonce_from_track_nonce_field"
}
```

- `action`: "view" | "download"
- `nonce`: Requerido. Crear con `wp_create_nonce('erm_track_' . $id)`. Se incluye en la respuesta de GET /resources.

**Headers:**
- `Content-Type: application/json`
- `X-WP-Nonce`: wp_rest nonce (para autenticación REST)

**Respuesta (200):**
```json
{
  "success": true,
  "message": "Acción registrada correctamente."
}
```

**Errores:**
- 403: invalid_nonce
- 404: Recurso no encontrado

---

### 4. GET /stats
Estadísticas. **Requiere:** usuario con `edit_posts`.

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

---

## Códigos de Error

| Código | Significado        |
|--------|--------------------|
| 200    | OK                 |
| 403    | Invalid nonce      |
| 404    | Not found          |
| 500    | Server error       |

---

## Ejemplo JavaScript

```javascript
// Listar recursos
const res = await fetch('/wp-json/erm/v1/resources?type=course', {
  headers: { 'X-WP-Nonce': ermData.nonce }
});
const resources = await res.json();

// Registrar tracking
await fetch(`/wp-json/erm/v1/resources/${id}/track`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': ermData.nonce
  },
  body: JSON.stringify({ action: 'view', nonce: resource.track_nonce })
});
```
