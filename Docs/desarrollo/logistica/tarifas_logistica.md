# Reglas de Tarifas de Logística — LDR Solutions

> **Fuente:** `Tarifas Vigentes.xlsx` — Ejercicio 2026, vigencia observada al 29 de agosto de 2026.
> **Propósito:** Este documento define las reglas de negocio para el cálculo de costos de traslado. Todo agente que modifique código relacionado con tarifas, costos o envíos **DEBE** leer y respetar estas reglas sin excepción.

---

## Definiciones Clave

| Término | Significado |
|---------|------------|
| **SLC** | Servicio Local Corto — de **0 a 40 km** de recorrido. Se cobra **tarifa plana** (NO por kilómetro). |
| **SLL** | Servicio Local Largo — de **41 a 80 km** de recorrido. Se cobra **tarifa plana** (NO por kilómetro). |
| **Foráneo** | Más de 80 km. Se cobra **por kilómetro recorrido**. |
| **Factoraje** | En Madrina y Plataforma, el precio por km por unidad varía según la cantidad de unidades transportadas simultáneamente. |
| **Segmento** | Clasificación del vehículo transportado: Ligero, Mediano, Pesado, Bus, Lowboy/Especial. |

---

## TABLA 1 — Rodando (Chofer)

**Concepto:** Un chofer maneja **1 solo vehículo** del punto A al punto B. No hay factoraje porque siempre es 1 unidad.

### Tarifas Autorizadas por Segmento

| Segmento | Modelos incluidos | $/KM Autorizado | SLC Autorizado | SLL Autorizado |
|----------|-------------------|-----------------|----------------|----------------|
| **Ligeros** | MILER, S3, S5, S6 | **$18.00** | **$1,440.00** | **$2,240.00** |
| **Medianos** | S8, S12, S20 | **$20.00** | **$1,440.00** | **$2,240.00** |
| **Pesados** | TRACTOS | **$25.00** | **$1,440.00** | **$2,240.00** |
| **Buses** | Todos los buses | **$25.00** | **$1,440.00** | **$2,240.00** |

### Reglas de Chofer (Rodando)

1. El costo siempre es `$/KM x distancia_km` para envíos foráneos (>80 km).
2. Para SLC (0-40 km): se cobra la tarifa plana SLC del segmento. **No se multiplica por km.**
3. Para SLL (41-80 km): se cobra la tarifa plana SLL del segmento. **No se multiplica por km.**
4. El segmento se determina por el tipo de vehículo que se está trasladando.
5. **No hay factoraje.** Siempre es 1 chofer = 1 vehículo.

---

## TABLA 2 — Madrina

**Concepto:** Un camión madrina transporta **múltiples vehículos** simultáneamente. El precio por km **por unidad** varía según cuántas unidades lleva (factoraje). A más unidades, menor costo por unidad.

### Tarifas Autorizadas — Factoraje ($/KM por unidad)

| Proveedor | 2 uds | 3 uds | 4 uds | 5 uds | 6 uds | 7 uds | 8 uds | 9 uds | SLC | SLL |
|-----------|-------|-------|-------|-------|-------|-------|-------|-------|-----|-----|
| **Logistica Teromo** | $45.00 | $35.00 | $28.00 | $25.00 | $22.00 | $20.00 | $20.00 | $20.00 | $3,850.00 | $5,780.00 |
| **Clinicar** | $40.00 | $26.66 | $17.00 | $17.00 | $17.00 | $15.00 | $15.00 | $13.00 | $3,850.00 | $5,780.00 |
| **Traslados Sacbe** | $17.00 | $17.00 | $17.00 | $17.00 | $17.00 | $15.00 | $15.00 | $13.00 | $3,850.00 | $5,780.00 |
| **Jose Daniel Garcia Vazquez** | $17.00 | $17.00 | $17.00 | $17.00 | $17.00 | $15.00 | $15.00 | $13.00 | $3,850.00 | $5,780.00 |
| **Automotive Translead** | $17.00 | $17.00 | $17.00 | $17.00 | $17.00 | $15.00 | $15.00 | $13.00 | $3,850.00 | $5,780.00 |
| **SCX** | $23.00 | $23.00 | $20.00 | $17.00 | $17.00 | $15.00 | $15.00 | $13.00 | $3,850.00 | $5,780.00 |

### Reglas de Madrina

1. El precio es **por unidad** y se multiplica: `$/KM_unidad x distancia_km x cantidad_unidades`.
2. **SLC y SLL en Madrina solo aplican A PARTIR DE 3 UNIDADES.**
   - Si una madrina lleva 1 o 2 unidades y el envío es local (SLC o SLL), **NO se debe permitir** usar tarifa de madrina local. Deben usar Plataforma u otra modalidad.
3. El precio de SLC ($3,850.00) y SLL ($5,780.00) es una **tarifa plana única** para todo el trayecto. **NO varía por cantidad de unidades.** Es el mismo precio si llevas 3 o 9 unidades.
4. El factoraje empieza desde **2 unidades**. No existe factor para 1 unidad en madrina (una madrina con 1 unidad no tiene sentido operativo, para eso se usa Rodando o Plataforma).
5. En el UI, el SLC y SLL de Madrina se muestra como **un solo campo por segmento** (no dentro de los factores).

---

## TABLA 3 — Plataforma y Lowboy

**Concepto:** Un camión plataforma transporta vehículos. El precio por km varía según la cantidad de unidades (1, 2 o 3). El Lowboy es un equipo especial que siempre transporta 1 unidad.

### Tarifas Autorizadas — $/KM por unidad según cantidad

| Proveedor | Plataforma 1 ud ($/km) | Plataforma 2 uds ($/km) | Plataforma 3 uds ($/km) | Lowboy Check 1 ud ($/km) |
|-----------|------------------------|-------------------------|-------------------------|--------------------------|
| **Clinicar** | $45.00 | $40.00 | $26.66 | $80.00 |
| **Victor Hugo Alatorre Gudino** | $45.00 | $45.00 | $45.00 | $80.00 |
| **Automotive Translead** | $45.00 | $40.00 | $17.00 | $80.00 |
| **Jose Daniel Garcia Vazquez** | $45.00 | $25.00 | $17.00 | $80.00 |
| **Logistica Teromo** | $45.00 | $45.00 | $35.00 | $80.00 |

### Tarifas Locales de Plataforma — SLC y SLL POR CANTIDAD DE UNIDADES

> [!IMPORTANT]
> A diferencia de Madrina, en Plataforma el SLC y SLL **SI varía según la cantidad de unidades**.

| Cantidad | SLC Autorizado | SLL Autorizado |
|----------|---------------|----------------|
| **1 Unidad** | **$4,320.00** | **$6,480.00** |
| **2 Unidades** | **$3,850.00** | **$5,780.00** |

### Reglas de Plataforma

1. Los factores de plataforma van de **1 a 3 unidades** (más el Lowboy como factor 4).
2. **SLC y SLL en Plataforma tienen 2 precios distintos:** uno para 1 unidad y otro para 2+ unidades. En el UI deben mostrarse **dentro de cada tarjeta de factor**, NO como un campo único por segmento.
3. El **Lowboy** (Factor 4 / "Check Lowboy") siempre es **$80.00/km** y transporta **1 sola unidad** especial.
4. El costo de Plataforma Foránea se calcula igual: `$/KM_unidad x distancia_km x cantidad_unidades`.
5. Para SLC y SLL: se cobra la **tarifa plana** correspondiente a la cantidad de unidades. NO se multiplica por km.

---

## Reglas Generales del Sistema

### Clasificación del Tipo de Servicio

| Distancia total del envío | Tipo de Servicio | Método de cobro |
|---------------------------|-----------------|-----------------|
| 0 - 40 km | **SLC** (Local Corto) | Tarifa plana |
| 41 - 80 km | **SLL** (Local Largo) | Tarifa plana |
| > 80 km | **Foráneo** | Por kilómetro |

### Determinación del Segmento Dominante

Cuando un envío transporta vehículos de distintos segmentos, se aplica la tarifa del **segmento más alto** (más costoso):

1. Lowboy/Especial (Segmento 5) — máxima prioridad
2. Buses (Segmento 4)
3. Pesados (Segmento 3)
4. Medianos (Segmento 2)
5. Ligeros (Segmento 1) — mínima prioridad

### Cálculo del Costo

**Foráneo (>80 km):**
```
costo = $/km_por_unidad x distancia_km x cantidad_unidades
```
- En Chofer: `costo = $/km_segmento x distancia_km` (siempre 1 unidad)
- En Madrina: `costo = $/km_factor[n_unidades] x distancia_km x n_unidades`
- En Plataforma: `costo = $/km_factor[n_unidades] x distancia_km x n_unidades`

**SLC o SLL (hasta 80 km):**
```
costo = tarifa_plana_slc_o_sll
```
- Es un precio fijo por el trayecto completo. **NO se multiplica por km ni por unidades.**
- La tarifa plana se selecciona según:
  - Tipo de traslado (Rodando, Madrina, Plataforma)
  - Segmento dominante (solo para Rodando)
  - Cantidad de unidades (solo para Plataforma: 1 ud vs 2 uds)

### Validaciones Obligatorias

1. **Madrina SLC/SLL requiere mínimo 3 unidades.** Si tiene menos, rechazar con error.
2. **SLC no debe superar 40 km.** Si el envío supera los 40 km y está clasificado como SLC, rechazar con error.
3. **SLL debe estar entre 41 y 80 km.** Si no cae en ese rango, rechazar con error.
4. **Foráneo debe ser >80 km.**

---

## Mapeo a la Base de Datos

| Campo DB (`lgs_tarifas_proveedores`) | Significado |
|--------------------------------------|------------|
| `id_tipo_traslado = 1` | Madrina |
| `id_tipo_traslado = 2` | Chofer (Rodando) |
| `id_tipo_traslado = 3` | Plataforma |
| `costo_por_km` | Precio base por kilómetro |
| `precio_plano` | Precio plano general (si aplica) |
| `precio_slc` | Tarifa plana para trayectos locales cortos (0-40 km) |
| `precio_sll` | Tarifa plana para trayectos locales largos (41-80 km) |
| `factor` | Multiplicador calculado (precio_real / costo_por_km) |
| `num_vins_min` / `num_vins_max` | Rango de unidades al que aplica este registro |
| `id_segmento` | Segmento de vehículo (1=Ligero, 2=Mediano, 3=Pesado, 4=Bus, 5=Lowboy) |

### Nota sobre Plataforma y SLC/SLL por factor

En Plataforma, `precio_slc` y `precio_sll` se guardan **por cada registro de factor** (num_vins_min=1 tiene SLC=4320, num_vins_min=2 tiene SLC=3850). Esto permite que el motor de costos lea el SLC/SLL correcto según la cantidad de unidades del envío.

En Madrina, `precio_slc` y `precio_sll` son **iguales para todos los factores** ($3,850 y $5,780 respectivamente), porque la tarifa local de madrina no varía por cantidad de unidades.

En Chofer, `precio_slc` y `precio_sll` varían **por segmento** (Ligeros=$1,440/$2,240, etc.), no por cantidad de unidades (siempre es 1).
