<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatosInicialesSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = [
            ['nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema'],
            ['nombre' => 'Contador', 'descripcion' => 'Gestión contable y financiera'],
            ['nombre' => 'Auxiliar', 'descripcion' => 'Operaciones básicas de registro'],
        ];

        foreach ($roles as $rol) {
            DB::table('roles')->insertOrIgnore(array_merge($rol, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Permisos
        $permisos = [
            'ver_clientes', 'crear_clientes', 'editar_clientes', 'eliminar_clientes',
            'ver_proveedores', 'crear_proveedores', 'editar_proveedores', 'eliminar_proveedores',
            'ver_productos', 'crear_productos', 'editar_productos', 'eliminar_productos',
            'ver_facturas', 'crear_facturas', 'editar_facturas', 'anular_facturas',
            'ver_compras', 'crear_compras', 'editar_compras',
            'ver_cuentas_cobrar', 'registrar_pago_cobrar',
            'ver_cuentas_pagar', 'registrar_pago_pagar',
            'ver_ingresos', 'crear_ingresos', 'editar_ingresos', 'eliminar_ingresos',
            'ver_gastos', 'crear_gastos', 'editar_gastos', 'eliminar_gastos',
            'ver_presupuesto', 'crear_presupuesto', 'editar_presupuesto', 'eliminar_presupuesto',
            'ver_reportes', 'generar_reportes',
            'ver_contabilidad', 'crear_asientos', 'editar_cuentas',
            'ver_inventario', 'crear_movimientos',
            'ver_consultas',
            'ver_usuarios', 'crear_usuarios', 'editar_usuarios', 'eliminar_usuarios',
            'ver_bitacora', 'ver_intentos_acceso',
            'ver_catalogos', 'editar_catalogos',
        ];

        foreach ($permisos as $permiso) {
            DB::table('permisos')->insertOrIgnore([
                'nombre' => $permiso,
                'descripcion' => str_replace('_', ' ', ucfirst($permiso)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Asignar todos los permisos al Administrador
        $adminRolId = DB::table('roles')->where('nombre', 'Administrador')->value('id');
        $todosPermisos = DB::table('permisos')->pluck('id');

        foreach ($todosPermisos as $permisoId) {
            DB::table('rol_permiso')->insertOrIgnore([
                'rol_id' => $adminRolId,
                'permiso_id' => $permisoId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Permisos del Contador
        $contadorRolId = DB::table('roles')->where('nombre', 'Contador')->value('id');
        $permisosContador = DB::table('permisos')
            ->whereIn('nombre', [
                'ver_clientes', 'ver_proveedores', 'ver_productos',
                'ver_facturas', 'crear_facturas',
                'ver_compras', 'crear_compras',
                'ver_cuentas_cobrar', 'registrar_pago_cobrar',
                'ver_cuentas_pagar', 'registrar_pago_pagar',
                'ver_ingresos', 'crear_ingresos', 'editar_ingresos',
                'ver_gastos', 'crear_gastos', 'editar_gastos',
                'ver_presupuesto', 'crear_presupuesto',
                'ver_reportes', 'generar_reportes',
                'ver_contabilidad', 'crear_asientos',
                'ver_inventario', 'crear_movimientos',
                'ver_consultas',
            ])
            ->pluck('id');

        foreach ($permisosContador as $permisoId) {
            DB::table('rol_permiso')->insertOrIgnore([
                'rol_id' => $contadorRolId,
                'permiso_id' => $permisoId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Permisos del Auxiliar
        $auxiliarRolId = DB::table('roles')->where('nombre', 'Auxiliar')->value('id');
        $permisosAuxiliar = DB::table('permisos')
            ->whereIn('nombre', [
                'ver_clientes', 'ver_proveedores', 'ver_productos',
                'ver_facturas', 'crear_facturas',
                'ver_compras',
                'ver_cuentas_cobrar', 'ver_cuentas_pagar',
                'ver_ingresos', 'ver_gastos',
                'ver_consultas',
            ])
            ->pluck('id');

        foreach ($permisosAuxiliar as $permisoId) {
            DB::table('rol_permiso')->insertOrIgnore([
                'rol_id' => $auxiliarRolId,
                'permiso_id' => $permisoId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Estados
        $estados = [
            ['nombre' => 'Pendiente', 'descripcion' => 'Registro pendiente de procesamiento'],
            ['nombre' => 'Pagado', 'descripcion' => 'Pago completado'],
            ['nombre' => 'Parcial', 'descripcion' => 'Pago parcial realizado'],
            ['nombre' => 'Vencido', 'descripcion' => 'Plazo de pago vencido'],
            ['nombre' => 'Anulado', 'descripcion' => 'Registro anulado'],
            ['nombre' => 'Activo', 'descripcion' => 'Registro activo'],
            ['nombre' => 'Inactivo', 'descripcion' => 'Registro inactivo'],
            ['nombre' => 'Aprobado', 'descripcion' => 'Registro aprobado'],
        ];

        foreach ($estados as $estado) {
            DB::table('estados')->insertOrIgnore(array_merge($estado, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Métodos de Pago
        $metodosPago = [
            ['nombre' => 'Efectivo', 'descripcion' => 'Pago en efectivo'],
            ['nombre' => 'Transferencia bancaria', 'descripcion' => 'Transferencia electrónica entre cuentas'],
            ['nombre' => 'Tarjeta de crédito', 'descripcion' => 'Pago con tarjeta de crédito'],
            ['nombre' => 'Tarjeta de débito', 'descripcion' => 'Pago con tarjeta de débito'],
            ['nombre' => 'Cheque', 'descripcion' => 'Pago mediante cheque'],
            ['nombre' => 'SINPE Móvil', 'descripcion' => 'Transferencia por SINPE Móvil'],
        ];

        foreach ($metodosPago as $metodo) {
            DB::table('metodos_pago')->insertOrIgnore(array_merge($metodo, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Tipos de Gasto
        $tiposGasto = [
            ['nombre' => 'Fijo', 'descripcion' => 'Gasto recurrente y predecible'],
            ['nombre' => 'Variable', 'descripcion' => 'Gasto que varía según la operación'],
            ['nombre' => 'Extraordinario', 'descripcion' => 'Gasto no previsto o eventual'],
        ];

        foreach ($tiposGasto as $tipo) {
            DB::table('tipos_gasto')->insertOrIgnore(array_merge($tipo, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Categorías de Gasto
        $categoriasGasto = [
            ['nombre' => 'Servicios públicos', 'descripcion' => 'Agua, electricidad, internet, teléfono'],
            ['nombre' => 'Alquiler', 'descripcion' => 'Alquiler de local o bodega'],
            ['nombre' => 'Salarios', 'descripcion' => 'Pago de planilla y cargas sociales'],
            ['nombre' => 'Transporte', 'descripcion' => 'Combustible, mantenimiento vehicular, envíos'],
            ['nombre' => 'Suministros', 'descripcion' => 'Materiales de oficina y operación'],
            ['nombre' => 'Mantenimiento', 'descripcion' => 'Reparaciones y mantenimiento de equipos'],
            ['nombre' => 'Impuestos', 'descripcion' => 'Obligaciones tributarias'],
            ['nombre' => 'Otros', 'descripcion' => 'Gastos no clasificados'],
        ];

        foreach ($categoriasGasto as $cat) {
            DB::table('categorias_gastos')->insertOrIgnore(array_merge($cat, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Tipos de Comprobante
        $tiposComprobante = [
            ['nombre' => 'Factura Electrónica', 'descripcion' => 'Comprobante electrónico válido ante Hacienda'],
            ['nombre' => 'Tiquete Electrónico', 'descripcion' => 'Tiquete para consumidor final'],
            ['nombre' => 'Nota de Crédito', 'descripcion' => 'Documento de ajuste por devolución o descuento'],
            ['nombre' => 'Nota de Débito', 'descripcion' => 'Documento de ajuste por cobro adicional'],
        ];

        foreach ($tiposComprobante as $tipo) {
            DB::table('tipos_comprobante')->insertOrIgnore(array_merge($tipo, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Tipos de Cuenta Contable
        $tiposCuenta = [
            ['nombre' => 'Activo', 'descripcion' => 'Bienes y derechos de la empresa'],
            ['nombre' => 'Pasivo', 'descripcion' => 'Obligaciones y deudas de la empresa'],
            ['nombre' => 'Patrimonio', 'descripcion' => 'Capital y reservas de los socios'],
            ['nombre' => 'Ingreso', 'descripcion' => 'Cuentas de ingresos operativos y no operativos'],
            ['nombre' => 'Gasto', 'descripcion' => 'Cuentas de gastos y costos operativos'],
        ];

        foreach ($tiposCuenta as $tipo) {
            DB::table('tipos_cuenta_contable')->insertOrIgnore(array_merge($tipo, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Tipos de Movimiento de Inventario
        $tiposMovimiento = [
            ['nombre' => 'Entrada', 'descripcion' => 'Ingreso de productos al inventario'],
            ['nombre' => 'Salida', 'descripcion' => 'Salida de productos del inventario'],
            ['nombre' => 'Ajuste positivo', 'descripcion' => 'Ajuste por diferencia a favor'],
            ['nombre' => 'Ajuste negativo', 'descripcion' => 'Ajuste por diferencia en contra'],
            ['nombre' => 'Devolución', 'descripcion' => 'Devolución de producto al inventario'],
        ];

        foreach ($tiposMovimiento as $tipo) {
            DB::table('tipos_movimiento_inventario')->insertOrIgnore(array_merge($tipo, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Catálogo de Cuentas Contables básico
        $cuentas = [
            ['codigo_cuenta' => '1', 'tipo' => 'Activo', 'nombre' => 'Activos'],
            ['codigo_cuenta' => '1.1', 'tipo' => 'Activo', 'nombre' => 'Activo Circulante'],
            ['codigo_cuenta' => '1.1.1', 'tipo' => 'Activo', 'nombre' => 'Caja'],
            ['codigo_cuenta' => '1.1.2', 'tipo' => 'Activo', 'nombre' => 'Bancos'],
            ['codigo_cuenta' => '1.1.3', 'tipo' => 'Activo', 'nombre' => 'Cuentas por Cobrar'],
            ['codigo_cuenta' => '1.1.4', 'tipo' => 'Activo', 'nombre' => 'Inventario de Mercaderías'],
            ['codigo_cuenta' => '1.2', 'tipo' => 'Activo', 'nombre' => 'Activo No Circulante'],
            ['codigo_cuenta' => '1.2.1', 'tipo' => 'Activo', 'nombre' => 'Mobiliario y Equipo'],
            ['codigo_cuenta' => '1.2.2', 'tipo' => 'Activo', 'nombre' => 'Vehículos'],
            ['codigo_cuenta' => '2', 'tipo' => 'Pasivo', 'nombre' => 'Pasivos'],
            ['codigo_cuenta' => '2.1', 'tipo' => 'Pasivo', 'nombre' => 'Pasivo Circulante'],
            ['codigo_cuenta' => '2.1.1', 'tipo' => 'Pasivo', 'nombre' => 'Cuentas por Pagar'],
            ['codigo_cuenta' => '2.1.2', 'tipo' => 'Pasivo', 'nombre' => 'Impuestos por Pagar'],
            ['codigo_cuenta' => '2.1.3', 'tipo' => 'Pasivo', 'nombre' => 'Salarios por Pagar'],
            ['codigo_cuenta' => '2.2', 'tipo' => 'Pasivo', 'nombre' => 'Pasivo No Circulante'],
            ['codigo_cuenta' => '2.2.1', 'tipo' => 'Pasivo', 'nombre' => 'Préstamos Bancarios'],
            ['codigo_cuenta' => '3', 'tipo' => 'Patrimonio', 'nombre' => 'Patrimonio'],
            ['codigo_cuenta' => '3.1', 'tipo' => 'Patrimonio', 'nombre' => 'Capital Social'],
            ['codigo_cuenta' => '3.2', 'tipo' => 'Patrimonio', 'nombre' => 'Utilidades Acumuladas'],
            ['codigo_cuenta' => '4', 'tipo' => 'Ingreso', 'nombre' => 'Ingresos'],
            ['codigo_cuenta' => '4.1', 'tipo' => 'Ingreso', 'nombre' => 'Ventas'],
            ['codigo_cuenta' => '4.2', 'tipo' => 'Ingreso', 'nombre' => 'Otros Ingresos'],
            ['codigo_cuenta' => '5', 'tipo' => 'Gasto', 'nombre' => 'Gastos'],
            ['codigo_cuenta' => '5.1', 'tipo' => 'Gasto', 'nombre' => 'Costo de Ventas'],
            ['codigo_cuenta' => '5.2', 'tipo' => 'Gasto', 'nombre' => 'Gastos Administrativos'],
            ['codigo_cuenta' => '5.3', 'tipo' => 'Gasto', 'nombre' => 'Gastos de Ventas'],
            ['codigo_cuenta' => '5.4', 'tipo' => 'Gasto', 'nombre' => 'Gastos Financieros'],
        ];

        foreach ($cuentas as $cuenta) {
            $tipoCuentaId = DB::table('tipos_cuenta_contable')
                ->where('nombre', $cuenta['tipo'])
                ->value('id');

            DB::table('catalogo_cuentas')->insertOrIgnore([
                'codigo_cuenta' => $cuenta['codigo_cuenta'],
                'tipo_cuenta_contable_id' => $tipoCuentaId,
                'nombre' => $cuenta['nombre'],
                'descripcion' => '',
                'estado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Categorías de Producto
        $categoriasProducto = [
            ['nombre' => 'Bebidas', 'descripcion' => 'Productos líquidos para consumo'],
            ['nombre' => 'Alimentos', 'descripcion' => 'Productos alimenticios'],
            ['nombre' => 'Limpieza', 'descripcion' => 'Productos de limpieza e higiene'],
            ['nombre' => 'Otros', 'descripcion' => 'Productos no clasificados'],
        ];

        foreach ($categoriasProducto as $cat) {
            DB::table('categorias_productos')->insertOrIgnore(array_merge($cat, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Usuario Administrador por defecto
        $adminExists = DB::table('users')->where('email', 'admin@ipacarai.com')->exists();

        if (!$adminExists) {
            DB::table('users')->insert([
                'rol_id' => $adminRolId,
                'name' => 'Administrador',
                'email' => 'admin@ipacarai.com',
                'password' => Hash::make('Ipacarai2026'),
                'estado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
