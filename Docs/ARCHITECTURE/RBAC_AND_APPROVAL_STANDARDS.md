# Estándar de Seguridad: RBAC, Scoping y Aprobaciones
**Versión:** 1.0  
**Estatus:** Obligatorio  
**Última Actualización:** Julio 2026

## 1. Filosofía del Sistema
Para garantizar la integridad de los datos y el cumplimiento normativo (Compliance), el sistema se basa en una arquitectura de **Seguridad por Diseño**. Ningún Service debe confiar en la petición del cliente; toda acción debe ser validada contra el **Contexto del Usuario** y su **Alcance (Scope)**.

---

## 2. El Cerebro del Acceso: `RoleEnum`
Toda la lógica de permisos jerárquicos reside en `Enums/RoleEnum.php`. Los Services **no deben** contener `if ($rol == 1)`; en su lugar, deben consumir los métodos del Enum.

### 2.1 Definición de Alcances (Data Scoping)
| Scope | Descripción | Implementación en SQL |
| :--- | :--- | :--- |
| `propio` | Solo registros del usuario logueado. | `WHERE usuarioid = :id` |
| `planta` | Registros de la planta del usuario. | `WHERE plantaid = :plantaid` |
| `total`  | Acceso irrestricto a nivel empresa. | *Sin filtro adicional* |

---

## 3. Protocolo de Visualización y Listados
Para mantener la consistencia en tablas y KPIs, se debe seguir el patrón de **Inyección de Filtros**.

### 3.1 Protección contra IDOR (Lectura de un solo registro)
Al consultar un detalle (ej: `getRequisition`), el Service debe validar el acceso **después** de obtener el registro de la DB:

```php
$record = $this->model->find($id);
$role = RoleEnum::from($userContext['rolid']);

if (!$role->canView($userContext, $record)) {
    throw new \Exception("Security Error: No tiene permisos para acceder a este recurso.", 403);
}
```

### 3.2 Filtrado de Colecciones (Listas y KPIs)
El Service solicita los filtros al Enum y los pasa directamente al Modelo:

```php
$role = RoleEnum::from($userContext['rolid']);
$filters = $role->getSQLFilters($userContext);

return $this->model->getAll($filters);
```

---

## 4. Protocolo de Aprobación Estándar (5 Pasos)
Todo método `approve` en cualquier Service (Requisiciones, Cuentas Bancarias, Facturas, etc.) debe seguir estrictamente este flujo de 5 bloques:

### Estructura Obligatoria del Código:

1.  **Autorización de Rol:** Validar si el rol tiene nivel de firma (`L1`, `L2`, `Ln`).
2.  **Validación de Contexto (Scope):** Verificar que el registro pertenezca al alcance del usuario (ej: misma planta).
3.  **Configuración de Transición:** Obtener los estados `from` y `to` mediante `$role->getTransitionConfig($entity)`.
4.  **Ejecución de Persistencia:** Llamar al método atómico `changeStatus()` con registro de auditoría.
5.  **Post-Hooks (Side Effects):** Disparar automatizaciones (ej: Generar OC automática o enviar correos).

---

## 5. Matriz de Transiciones (Máquina de Estados)
Las transiciones se definen centralmente en el `RoleEnum` para evitar estados inconsistentes.

```php
// Ejemplo de configuración en RoleEnum
'bank_account' => [
    'L1' => ['from' => ['pendiente'], 'to' => 'validada_tesoreria'],
    'L2' => ['from' => ['validada_tesoreria'], 'to' => 'activa']
]
```

---

## 6. Políticas de Mutación (Crear, Editar, Borrar)
Antes de ejecutar un `UPDATE` o `DELETE`, el desarrollador debe invocar la validación de propiedad:

```php
$isOwner = (int)$record['usuarioid'] === (int)$userContext['id'];
$isSameDept = (int)$record['plantaid'] === (int)$userContext['plantaid'];

if (!$role->canMutate($isOwner, $isSameDept)) {
    throw new \Exception("No tiene privilegios para modificar este registro.", 403);
}
```

---

## 7. Glosario de Error Codes
*   **403 Forbidden:** El rol no tiene el nivel de firma o el registro está fuera de su scope.
*   **404 Not Found:** El registro no existe o el ID es inválido.
*   **409 Conflict:** El registro no está en el estado (`from`) requerido para la transición actual.

---
> **Aviso:** El incumplimiento de estos patrones de diseño resultará en el rechazo del código durante la revisión de seguridad.