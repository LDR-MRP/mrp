# Documentación y Diccionario de Datos de la Base de Datos MRP (`db_mrp`)

Este directorio contiene el mapa y diccionario completo de tablas, campos y relaciones del sistema **MRP**.
Esta documentación ha sido estructurada de forma modular para brindar un contexto claro a **Desarrolladores y Agentes IA**.

---

## 📐 Convención de Nombres de Tablas (Taxonomía)

| Prefijo | Tipo de Tabla | Descripción | Ejemplo |
| :--- | :--- | :--- | :--- |
| `mrp_` | Producción / MRP | Módulos de manufactura, estaciones, BOM, órdenes de trabajo, calidad PDI. | `mrp_ordenes_trabajo` |
| `wms_` | Almacenes e Inventario | Gestión de stock, movimientos, kardex, lotes, series, precios y ubicaciones. | `wms_inventario` |
| `com_` | Compras | Requisiciones, órdenes de compra y cotizaciones. | `com_ordenes_compra` |
| `prv_` | Proveedores | Catálogo de proveedores, onboarding, contactos y expediente financiero. | `prv_cat_proveedores` |
| `cli_` | Clientes | Catálogo de clientes, sucursales, direcciones, usuarios de portal. | `cli_clientes` |
| `cat_` | Catálogos Generales | Monedas, bancos, estados, VIN, códigos postales. | `cat_paises` |
| `sat_` | Catálogos SAT | Regímenes fiscales, formas de pago, uso de CFDI. | `sat_cat_forma_pago` |
| `lgs_` | Logística y Traslados | Módulos de envíos, asignación de nodrizas, choferes, costeo, paradas y checklists. | `lgs_envios` |
| `log_` | Auditoría | Registros inmutables de eventos y accesos al sistema. | `log_audit` |

---

## 📂 Módulos de la Base de Datos

Explora la documentación detallada por área funcional:

1. 🏭 **[MODULE_MRP_PRODUCTION.md](MODULE_MRP_PRODUCTION.md)**: Producción, Estaciones de Trabajo, BOM, Órdenes de Trabajo, Planeación, Calidad PDI y Rutas.
2. 📦 **[MODULE_WMS_INVENTORY.md](MODULE_WMS_INVENTORY.md)**: Inventarios, Almacenes, Movimientos, Precios, Monedas, Lotes, Series y Ubicaciones.
3. 🛒 **[MODULE_PURCHASING_COM_PRV.md](MODULE_PURCHASING_COM_PRV.md)**: Compras, Requisiciones, Cotizaciones, Proveedores, Expedientes y Cuentas por Pagar.
4. 🚚 **[MODULE_LOGISTICS_LGS.md](MODULE_LOGISTICS_LGS.md)**: Logística Operativa, Madrinas, Choferes, Tarifario por Segmento, Envíos, Paradas, Aprobaciones y Evidencias.
5. 👥 **[MODULE_CUSTOMERS_CLI.md](MODULE_CUSTOMERS_CLI.md)**: Clientes, Sucursales, Direcciones, Contactos y Usuarios de Acceso.
6. ⚙️ **[MODULE_CATALOGS_SAT_SYS.md](MODULE_CATALOGS_SAT_SYS.md)**: Catálogos Maestros (Bancos, VIN, CP, Estados), Facturación SAT, Usuarios, Roles, Permisos y Bitácoras.
