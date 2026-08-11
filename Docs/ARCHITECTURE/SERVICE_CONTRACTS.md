# Guía de Estándares: Capa de Servicios (Services)
**Versión:** 1.0  
**Estatus:** Obligatorio  
**Última Actualización:** Julio 2026

## 1. Introducción
Para garantizar la escalabilidad, la consistencia de las respuestas de la API y la facilidad de depuración en nuestro sistema MRP, todos los métodos desarrollados en la carpeta `Services/` deben seguir estrictamente este contrato.

El objetivo es separar la **Lógica de Negocio** de la **Infraestructura** y asegurar que el Frontend siempre reciba una estructura predecible.

---

## 2. El Ciclo de Vida de una Petición (Try-Catch Maestro)
Implementamos una gestión de excepciones en 3 niveles de gravedad. Esta estructura es innegociable para mantener la integridad de los datos (ACID).

### 2.1 Niveles de Excepción

| Nivel | Excepción | Código HTTP | Nivel de Log | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| **1** | `InvalidArgumentException` | 422 | *Ninguno* | Errores de validación de entrada (FormRequests). |
| **2** | `Exception` | 400 - 409 | **WARNING** | Violación de reglas de negocio (SoD, Estatus inválido). |
| **3** | `PDOException` o `Error` | 500 | **CRITICAL** | Fallas de base de datos, sintaxis PHP o infraestructura. |

---

## 3. Plantillas Maestras

### 3.1 Contrato para Métodos de LECTURA (GET)
Los métodos de lectura deben priorizar la velocidad. No inician transacciones a menos que sea estrictamente necesario por bloqueos (Locking).

```php
public function getResource(int $id, array $userContext): ServiceResponse
{
    try {
        // 1. Lógica de recuperación
        $data = $this->model->find($id);
        if (!$data) throw new \Exception("Recurso no encontrado.", 404);

        return ServiceResponse::success($data);

    } catch (\InvalidArgumentException $i) {
        return ServiceResponse::validation($i->getMessage());
    } catch (\Exception $e) {
        $this->logMessage($e, \LogLevel::WARNING, ['id' => $id]);
        $code = ($e->getCode() >= 400 && $code < 600) ? $e->getCode() : 500;
        return ServiceResponse::error($e->getMessage(), (int)$code);
    } catch (\PDOException | \Error $p) {
        $this->logMessage($p, \LogLevel::CRITICAL, ['action' => 'read_resource']);
        return ServiceResponse::error("Error de integridad en la base de datos.", 500);
    }
}
```

### 3.2 Contrato para Métodos de ESCRITURA (POST, PUT, DELETE)
La **Atomicidad** es obligatoria. Todo método que altere la DB debe estar envuelto en una transacción.

```php
public function storeResource(array $payload, array $userContext): ServiceResponse
{
    try {
        $this->db->beginTransaction();

        // 1. Lógica de persistencia
        // ...
        
        $this->db->commit();
        return ServiceResponse::success(null, "Operación exitosa.", 201);

    } catch (\InvalidArgumentException $i) {
        if ($this->db->inTransaction()) $this->db->rollBack();
        return ServiceResponse::validation($i->getMessage());
    } catch (\Exception $e) {
        if ($this->db->inTransaction()) $this->db->rollBack();
        $this->logMessage($e, \LogLevel::WARNING, ['payload' => $payload]);
        $code = ($e->getCode() >= 400 && $code < 600) ? $e->getCode() : 500;
        return ServiceResponse::error($e->getMessage(), (int)$code);
    } catch (\PDOException | \Error $p) {
        if ($this->db->inTransaction()) $this->db->rollBack();
        $this->logMessage($p, \LogLevel::CRITICAL, ['context' => 'write_fail']);
        return ServiceResponse::error("Falla crítica de persistencia. Operación revertida.", 500);
    }
}
```

---

## 4. Herramientas de Productividad (VS Code)
Para facilitar la adopción, se han creado snippets oficiales. Escribe el prefijo y presiona `Tab`:
*   `srv-get`: Genera la estructura para métodos de lectura.
*   `srv-write`: Genera la estructura para métodos de escritura con transacciones.

---

## 5. Futuros Contratos (Backlog)
*   [ ] **Contrato #2:** Estándares de nombrado en Modelos (Eloquent-Ready).
*   [ ] **Contrato #3:** Consumo de APIs en Frontend (Motor de Red Sys_Core).
*   [ ] **Contrato #4:** Políticas de Segregación de Funciones (SoD).

---
> **Nota:** El incumplimiento de estos estándares resultará en el rechazo automático del Pull Request (PR).