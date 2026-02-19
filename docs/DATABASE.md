# Documentación de Base de Datos - Education Resources Manager

## Esquema de Datos

### Tabla Personalizada: `{prefix}_erm_resource_tracking`

Almacena el registro de visualizaciones y descargas de cada recurso. Se crea automáticamente al activar el plugin.

| Columna     | Tipo         | Descripción                    |
|-------------|--------------|--------------------------------|
| id          | BIGINT(20)   | PK, AUTO_INCREMENT             |
| resource_id | BIGINT(20)   | FK → wp_posts.ID               |
| user_id     | BIGINT(20)   | FK → wp_users.ID (0 = anónimo) |
| action_date | DATETIME     | DEFAULT CURRENT_TIMESTAMP     |
| action_type | VARCHAR(20)  | 'view' o 'download'            |
| ip_address  | VARCHAR(45)  | IPv4/IPv6                      |

### Índices
- PRIMARY KEY (id)
- KEY resource_id
- KEY user_id
- KEY action_date
- KEY action_type

### SQL de Creación

```sql
CREATE TABLE IF NOT EXISTS `{prefix}_erm_resource_tracking` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `action_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `action_type` varchar(20) NOT NULL DEFAULT 'view',
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `user_id` (`user_id`),
  KEY `action_date` (`action_date`),
  KEY `action_type` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Nota:** Sustituir `{prefix}` por el prefijo de tablas de WordPress (ej. `wp_`).

---

## Post Meta (wp_postmeta)

Los recursos educativos (CPT `erm_resource`) almacenan sus campos personalizados en `wp_postmeta`:

| Meta Key                 | Descripción              | Valores                    |
|--------------------------|--------------------------|----------------------------|
| _erm_resource_type       | Tipo de recurso          | course, tutorial, ebook, video |
| _erm_difficulty_level    | Nivel de dificultad      | beginner, intermediate, advanced |
| _erm_duration            | Duración en minutos      | integer                    |
| _erm_resource_url       | URL del recurso externo  | URL                        |
| _erm_instructor          | Instructor/Autor         | string                     |
| _erm_price               | Precio                   | string (0/Gratuito)        |
| _erm_publication_status  | Estado (legacy)          | draft, published, archived |

**Nota:** El estado se sincroniza con `wp_posts.post_status`. El valor `archived` se registra como post_status personalizado. Valores válidos: `draft`, `publish`, `archived`.

---

## Opciones (wp_options)

| Option Key           | Descripción                              | Valores                    |
|----------------------|------------------------------------------|----------------------------|
| erm_db_version       | Versión del esquema de BD (activación)   | string                     |
| erm_excerpt_max_chars| Máximo de caracteres en extracto        | integer (default: 150)     |
| erm_single_layout    | Layout de vista individual               | default, side-by-side      |

---

## Taxonomías

- **erm_resource_category** (jerárquica) – Categorías de recursos
- **erm_skill_tag** (no jerárquica) – Etiquetas de habilidades

---

## Queries Principales

### 1. Registrar tracking
```php
$wpdb->insert($table_name, [
    'resource_id' => $resource_id,
    'user_id'     => get_current_user_id(),
    'action_type' => 'view',
    'ip_address'  => $ip
], ['%d', '%d', '%s', '%s']);
```

### 2. Top 5 más vistos
```php
$wpdb->get_results($wpdb->prepare(
    "SELECT resource_id, COUNT(*) as view_count 
    FROM {$table_name} 
    WHERE action_type = 'view' 
    GROUP BY resource_id 
    ORDER BY view_count DESC 
    LIMIT %d", 5
));
```

### 3. Recursos por mes (últimos 6)
```php
$wpdb->get_results($wpdb->prepare(
    "SELECT DATE_FORMAT(post_date, '%%Y-%%m') as month, COUNT(*) as count 
    FROM {$wpdb->posts} 
    WHERE post_type = %s AND post_status = 'publish' 
    AND post_date >= DATE_SUB(CURDATE(), INTERVAL %d MONTH) 
    GROUP BY month ORDER BY month ASC",
    ERM_Post_Type::POST_TYPE, 6
));
```

### 4. Conteo por tipo
```php
$wpdb->get_results($wpdb->prepare(
    "SELECT pm.meta_value as type, COUNT(*) as count 
    FROM {$wpdb->postmeta} pm 
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
    WHERE pm.meta_key = %s AND p.post_type = %s 
    AND p.post_status = 'publish' 
    GROUP BY pm.meta_value",
    ERM_Post_Type::META_TYPE, ERM_Post_Type::POST_TYPE
));
```
