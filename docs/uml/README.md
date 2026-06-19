# Diagramas UML - Sistema Contable Ipacaraí

## Contenido

| # | Archivo | Tipo de Diagrama | Descripción |
|---|---------|------------------|-------------|
| 01 | `01-diagrama-casos-de-uso.puml` | Casos de Uso | Actores y funcionalidades del sistema por módulo |
| 02 | `02-diagrama-clases.puml` | Clases | Modelos Eloquent con atributos, métodos y relaciones |
| 03 | `03-diagrama-entidad-relacion.puml` | Entidad-Relación | Todas las 30 tablas con PKs, FKs y cardinalidades |
| 04 | `04-diagrama-secuencia-facturacion.puml` | Secuencia | Proceso de creación de factura |
| 05 | `05-diagrama-secuencia-compras.puml` | Secuencia | Proceso de registro de compra |
| 06 | `06-diagrama-secuencia-login.puml` | Secuencia | Proceso de autenticación y verificación de permisos |
| 07 | `07-diagrama-actividades-facturacion.puml` | Actividades | Ciclo de vida completo de una factura |
| 08 | `08-diagrama-componentes.puml` | Componentes | Arquitectura MVC del sistema Laravel |
| 09 | `09-diagrama-despliegue.puml` | Despliegue | Infraestructura y servicios externos |
| 10 | `10-diagrama-secuencia-pago-cxc.puml` | Secuencia | Proceso de pago de cuenta por cobrar |
| 11 | `11-diagrama-actividades-compras.puml` | Actividades | Ciclo de vida de una compra |
| 12 | `12-diagrama-estados-factura.puml` | Estados | Transiciones de estado de una factura |

## Cómo visualizar los diagramas

### Opción 1: PlantUML Online
1. Ir a [https://www.plantuml.com/plantuml/uml](https://www.plantuml.com/plantuml/uml)
2. Copiar y pegar el contenido del archivo `.puml`
3. Se renderiza automáticamente

### Opción 2: VS Code
1. Instalar la extensión **PlantUML** (`jebbs.plantuml`)
2. Abrir cualquier archivo `.puml`
3. Presionar `Alt+D` para previsualizar

### Opción 3: Línea de comandos
```bash
# Instalar PlantUML (requiere Java)
# Generar PNG de todos los diagramas
java -jar plantuml.jar docs/uml/*.puml
```

## Módulos del Sistema

- **Seguridad**: Usuarios, roles, permisos, bitácora, intentos de acceso
- **Facturación**: Facturas, detalle, factura electrónica, anulaciones, envíos
- **Compras**: Compras, detalle de compras
- **Contabilidad**: Catálogo de cuentas, asientos contables
- **Cuentas por Cobrar**: Seguimiento de cobros y pagos de clientes
- **Cuentas por Pagar**: Seguimiento de pagos a proveedores
- **Ingresos y Gastos**: Registro financiero y presupuesto
- **Inventario**: Movimientos de inventario y control de stock
- **Reportes y Consultas**: Generación de reportes PDF y consultas avanzadas
