# Reglas de Cálculo de Costos de Envío (Logística)

A continuación, se detalla la lógica y reglas completas que el motor del sistema aplica para calcular los costos de envío. Las reglas se dividen por el **tipo de traslado**, la **distancia** y el **segmento**.

---

## 📍 1. Tipos de Servicio por Distancia

La distancia total del recorrido determina la modalidad de cobro:

| Clasificación | Rango de Distancia | Método de Cobro |
|---------------|-------------------|-----------------|
| **SLC** (Servicio Local Corto) | **0 a 40 km** | **Tarifa Plana** (Precio fijo único por trayecto) |
| **SLL** (Servicio Local Largo) | **41 a 80 km** | **Tarifa Plana** (Precio fijo único por trayecto) |
| **Foráneo** | **Mayor a 80 km** | **Por Kilómetro** (Tarifa factorizada x KM x N° Vehículos) |

---

## 🚛 2. Reglas por Modalidad de Traslado

### A. Rodando (Chofer)
- **Concepto:** 1 Chofer maneja 1 solo vehículo.
- **Factoraje:** **NO aplica.** Siempre es 1 unidad.
- **Segmento Dominante:** El precio se basa en la clasificación del vehículo.
  - **Ligeros:** $18.00 / km | SLC: $1,440.00 | SLL: $2,240.00
  - **Medianos:** $20.00 / km | SLC: $1,440.00 | SLL: $2,240.00
  - **Pesados / Buses:** $25.00 / km | SLC: $1,440.00 | SLL: $2,240.00
- **Fórmula Foráneo:** `$/km_autorizado × distancia_km`
- **Fórmula SLC/SLL:** `tarifa_plana_autorizada_segun_segmento` *(No se multiplica por los km)*.

### B. Madrina
- **Concepto:** 1 camión madrina transporta múltiples unidades simultáneamente.
- **Factoraje:** **SÍ aplica.** El precio **por unidad** disminuye cuantas más unidades viajen juntas en el mismo envío.
  - 2 uds: ~$40.00 - $45.00/km (Depende proveedor)
  - 3 uds: ~$26.66 - $35.00/km
  - 4 uds: ~$17.00 - $28.00/km
  - 5 a 6 uds: ~$17.00 - $25.00/km
  - 7 a 10 uds: ~$13.00 - $20.00/km
- **Regla Local (SLC / SLL):** 
  - **Madrina en SLC/SLL requiere mínimo 3 unidades.** (Si son 1 o 2 unidades y la distancia es menor a 80km, no se permite madrina; debe ser Plataforma).
  - El costo SLC es de **$3,850.00** y SLL de **$5,780.00**. Este es un costo **ÚNICO Y PLANO**, no importa si la madrina lleva 3 o 9 vehículos, el costo final no se multiplica.
- **Fórmula Foráneo:** `($/km_del_factor × distancia_km) × cantidad_total_de_unidades`

### C. Plataforma y Lowboy
- **Concepto:** Transporte en plancha para 1, 2 o hasta 3 vehículos. Lowboy es exclusivo para equipo especial.
- **Factoraje:** **SÍ aplica** (Para Plataforma de 1 a 3 unidades).
  - 1 ud: $45.00/km
  - 2 uds: $25.00 a $45.00/km
  - 3 uds: $17.00 a $35.00/km
- **Regla Local (SLC / SLL):**
  - **SÍ varía** por cantidad de unidades.
  - 1 Unidad: SLC **$4,320.00** | SLL **$6,480.00**
  - 2+ Unidades: SLC **$3,850.00** | SLL **$5,780.00**
- **Lowboy:** Precio fijo de **$80.00/km** (Siempre es 1 sola unidad).
- **Fórmula Foráneo:** `($/km_del_factor × distancia_km) × cantidad_total_de_unidades`

---

## ⚖️ 3. Jerarquía y Segmento Dominante

Si un envío, por ejemplo en Plataforma o Madrina, mezcla vehículos de diferentes segmentos (ej. un Ligero y un Mediano), la regla exige que se aplique la tarifa o restricción del **segmento más caro (Dominante)** según la siguiente jerarquía de mayor a menor prioridad:

1. Lowboy / Especial (Máxima)
2. Buses
3. Pesados
4. Medianos
5. Ligeros (Mínima)

---

## 🚫 4. Reglas de Validación (Bloqueos)

1. **Topes de Km:** Un envío categorizado como **SLC** será rechazado por el sistema si su distancia total registrada supera los 40 km. Un **SLL** si sale del rango de 41 a 80 km.
2. **Sentido Lógico de Madrina Local:** Se rechazará la creación de un envío local (≤ 80km) en Madrina si este lleva menos de 3 vehículos.
