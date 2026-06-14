<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | CATÁLOGOS
        | El profesor indicó que los catálogos sí pueden usar ID simple.
        |--------------------------------------------------------------------------
        */

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('categorias_productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('categorias_gastos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('tipos_gasto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('estados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('tipos_comprobante', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('tipos_cuenta_contable', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('tipos_movimiento_inventario', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        /*
        |--------------------------------------------------------------------------
        | SEGURIDAD Y DATOS MAESTROS
        |--------------------------------------------------------------------------
        */

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token', 100)->default('');
            $table->boolean('estado')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['rol_id', 'permiso_id']);
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('identificacion')->unique();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('telefono')->default('');
            $table->string('direccion', 500)->default('');
            $table->boolean('estado')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('identificacion')->unique();
            $table->string('nombre');
            $table->string('empresa')->default('');
            $table->string('telefono')->default('');
            $table->string('correo')->unique();
            $table->boolean('estado')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_producto_id')->constrained('categorias_productos')->restrictOnDelete();
            $table->string('codigo_barras')->unique();
            $table->string('nombre');
            $table->string('descripcion', 500)->default('');
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->decimal('precio', 10, 2)->default(0);
            $table->boolean('estado')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        /*
        |--------------------------------------------------------------------------
        | GESTIÓN CONTABLE
        |--------------------------------------------------------------------------
        */

        Schema::create('catalogo_cuentas', function (Blueprint $table) {
            $table->string('codigo_cuenta')->primary();
            $table->foreignId('tipo_cuenta_contable_id')->constrained('tipos_cuenta_contable')->restrictOnDelete();
            $table->string('nombre');
            $table->string('descripcion', 500)->default('');
            $table->boolean('estado')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('asientos_contables', function (Blueprint $table) {
            $table->string('numero_asiento');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->string('descripcion', 500)->default('');
            $table->decimal('total_debe', 10, 2)->default(0);
            $table->decimal('total_haber', 10, 2)->default(0);
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_asiento', 'fecha']);
        });

        Schema::create('detalle_asientos_contables', function (Blueprint $table) {
            $table->string('numero_asiento');
            $table->date('fecha_asiento');
            $table->string('codigo_cuenta');
            $table->decimal('debe', 10, 2)->default(0);
            $table->decimal('haber', 10, 2)->default(0);
            $table->string('descripcion', 500)->default('');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_asiento', 'fecha_asiento', 'codigo_cuenta']);

            $table->foreign(['numero_asiento', 'fecha_asiento'])
                ->references(['numero_asiento', 'fecha'])
                ->on('asientos_contables')
                ->cascadeOnDelete();

            $table->foreign('codigo_cuenta')
                ->references('codigo_cuenta')
                ->on('catalogo_cuentas')
                ->restrictOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | FACTURACIÓN ELECTRÓNICA
        |--------------------------------------------------------------------------
        */

        Schema::create('facturas', function (Blueprint $table) {
            $table->string('numero_factura');
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->restrictOnDelete();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->foreignId('tipo_comprobante_id')->constrained('tipos_comprobante')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('impuesto', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_factura', 'cliente_id']);
        });

        Schema::create('detalle_facturas', function (Blueprint $table) {
            $table->string('numero_factura');
            $table->unsignedBigInteger('cliente_id');
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_factura', 'cliente_id', 'producto_id']);

            $table->foreign(['numero_factura', 'cliente_id'])
                ->references(['numero_factura', 'cliente_id'])
                ->on('facturas')
                ->cascadeOnDelete();
        });

        Schema::create('facturas_electronicas', function (Blueprint $table) {
            $table->string('numero_factura');
            $table->unsignedBigInteger('cliente_id');
            $table->string('clave_hacienda')->unique();
            $table->string('consecutivo_hacienda')->unique();
            $table->string('estado_hacienda');
            $table->string('mensaje_hacienda', 1000)->default('');
            $table->string('xml_generado', 1000)->default('');
            $table->dateTime('fecha_envio')->useCurrent();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_factura', 'cliente_id', 'clave_hacienda']);

            $table->foreign(['numero_factura', 'cliente_id'])
                ->references(['numero_factura', 'cliente_id'])
                ->on('facturas')
                ->cascadeOnDelete();
        });

        Schema::create('anulaciones_facturas', function (Blueprint $table) {
            $table->string('numero_factura');
            $table->unsignedBigInteger('cliente_id');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->string('motivo', 500);
            $table->date('fecha_anulacion');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_factura', 'cliente_id', 'fecha_anulacion']);

            $table->foreign(['numero_factura', 'cliente_id'])
                ->references(['numero_factura', 'cliente_id'])
                ->on('facturas')
                ->cascadeOnDelete();
        });

        Schema::create('envios_comprobantes', function (Blueprint $table) {
            $table->string('numero_factura');
            $table->unsignedBigInteger('cliente_id');
            $table->string('correo_destino');
            $table->string('estado_envio');
            $table->string('mensaje', 500)->default('');
            $table->dateTime('fecha_envio')->useCurrent();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_factura', 'cliente_id', 'fecha_envio']);

            $table->foreign(['numero_factura', 'cliente_id'])
                ->references(['numero_factura', 'cliente_id'])
                ->on('facturas')
                ->cascadeOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | COMPRAS Y PROVEEDORES
        |--------------------------------------------------------------------------
        */

        Schema::create('compras', function (Blueprint $table) {
            $table->string('numero_compra');
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('impuesto', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_compra', 'proveedor_id']);
        });

        Schema::create('detalle_compras', function (Blueprint $table) {
            $table->string('numero_compra');
            $table->unsignedBigInteger('proveedor_id');
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_compra', 'proveedor_id', 'producto_id']);

            $table->foreign(['numero_compra', 'proveedor_id'])
                ->references(['numero_compra', 'proveedor_id'])
                ->on('compras')
                ->cascadeOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | CUENTAS POR COBRAR
        |--------------------------------------------------------------------------
        */

        Schema::create('cuentas_cobrar', function (Blueprint $table) {
            $table->string('numero_factura');
            $table->unsignedBigInteger('cliente_id');
            $table->decimal('monto_original', 10, 2);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_factura', 'cliente_id']);

            $table->foreign(['numero_factura', 'cliente_id'])
                ->references(['numero_factura', 'cliente_id'])
                ->on('facturas')
                ->cascadeOnDelete();
        });

        Schema::create('pagos_clientes', function (Blueprint $table) {
            $table->string('numero_factura');
            $table->unsignedBigInteger('cliente_id');
            $table->string('referencia_pago');
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_factura', 'cliente_id', 'referencia_pago']);

            $table->foreign(['numero_factura', 'cliente_id'])
                ->references(['numero_factura', 'cliente_id'])
                ->on('facturas')
                ->cascadeOnDelete();
        });

        Schema::create('alertas_morosidad', function (Blueprint $table) {
            $table->string('numero_factura');
            $table->unsignedBigInteger('cliente_id');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->integer('dias_atraso')->default(0);
            $table->string('mensaje', 500)->default('');
            $table->date('fecha_alerta');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_factura', 'cliente_id', 'fecha_alerta']);

            $table->foreign(['numero_factura', 'cliente_id'])
                ->references(['numero_factura', 'cliente_id'])
                ->on('facturas')
                ->cascadeOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | CUENTAS POR PAGAR
        |--------------------------------------------------------------------------
        */

        Schema::create('cuentas_pagar', function (Blueprint $table) {
            $table->string('numero_compra');
            $table->unsignedBigInteger('proveedor_id');
            $table->decimal('monto_original', 10, 2);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_compra', 'proveedor_id']);

            $table->foreign(['numero_compra', 'proveedor_id'])
                ->references(['numero_compra', 'proveedor_id'])
                ->on('compras')
                ->cascadeOnDelete();
        });

        Schema::create('pagos_proveedores', function (Blueprint $table) {
            $table->string('numero_compra');
            $table->unsignedBigInteger('proveedor_id');
            $table->string('referencia_pago');
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_compra', 'proveedor_id', 'referencia_pago']);

            $table->foreign(['numero_compra', 'proveedor_id'])
                ->references(['numero_compra', 'proveedor_id'])
                ->on('compras')
                ->cascadeOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | INGRESOS, GASTOS Y PRESUPUESTO
        |--------------------------------------------------------------------------
        */

        Schema::create('ingresos', function (Blueprint $table) {
            $table->string('referencia_ingreso');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->restrictOnDelete();
            $table->string('origen');
            $table->string('descripcion', 500)->default('');
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['referencia_ingreso', 'fecha', 'usuario_id']);
        });

        Schema::create('gastos', function (Blueprint $table) {
            $table->string('numero_comprobante');
            $table->foreignId('categoria_gasto_id')->constrained('categorias_gastos')->restrictOnDelete();
            $table->foreignId('tipo_gasto_id')->constrained('tipos_gasto')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->restrictOnDelete();
            $table->string('descripcion', 500)->default('');
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['numero_comprobante', 'categoria_gasto_id', 'fecha']);
        });

        Schema::create('presupuestos', function (Blueprint $table) {
            $table->string('periodo');
            $table->string('nombre');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->string('descripcion', 500)->default('');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('monto_total', 10, 2);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['periodo', 'nombre']);
        });

        Schema::create('detalle_presupuestos', function (Blueprint $table) {
            $table->string('periodo');
            $table->string('nombre_presupuesto');
            $table->foreignId('categoria_gasto_id')->constrained('categorias_gastos')->restrictOnDelete();
            $table->decimal('monto_presupuestado', 10, 2);
            $table->decimal('monto_ejecutado', 10, 2)->default(0);
            $table->decimal('diferencia', 10, 2)->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['periodo', 'nombre_presupuesto', 'categoria_gasto_id']);

            $table->foreign(['periodo', 'nombre_presupuesto'])
                ->references(['periodo', 'nombre'])
                ->on('presupuestos')
                ->cascadeOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | INVENTARIO
        |--------------------------------------------------------------------------
        */

        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->string('referencia_movimiento');
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('tipo_movimiento_inventario_id')->constrained('tipos_movimiento_inventario')->restrictOnDelete();
            $table->integer('cantidad');
            $table->string('descripcion', 500)->default('');
            $table->date('fecha');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['referencia_movimiento', 'producto_id', 'tipo_movimiento_inventario_id']);
        });

        /*
        |--------------------------------------------------------------------------
        | TRAZABILIDAD, REPORTES, CONSULTAS Y SEGURIDAD
        |--------------------------------------------------------------------------
        */

        Schema::create('historial_saldos', function (Blueprint $table) {
            $table->string('referencia_documento');
            $table->string('tipo_documento');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->decimal('saldo_anterior', 10, 2);
            $table->decimal('monto_movimiento', 10, 2);
            $table->decimal('saldo_nuevo', 10, 2);
            $table->dateTime('fecha')->useCurrent();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['referencia_documento', 'tipo_documento', 'fecha']);
        });

        Schema::create('reportes_generados', function (Blueprint $table) {
            $table->string('codigo_reporte');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo_reporte');
            $table->string('formato')->default('PDF');
            $table->string('ruta_archivo', 500)->default('');
            $table->string('parametros', 1000)->default('');
            $table->string('descripcion', 500)->default('');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->dateTime('fecha_generacion')->useCurrent();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['codigo_reporte', 'usuario_id', 'fecha_generacion']);
        });

        Schema::create('consultas_realizadas', function (Blueprint $table) {
            $table->string('codigo_consulta');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('modulo');
            $table->string('criterio_busqueda', 500)->default('');
            $table->dateTime('fecha_consulta')->useCurrent();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['codigo_consulta', 'usuario_id', 'fecha_consulta']);
        });

        Schema::create('bitacora', function (Blueprint $table) {
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('accion');
            $table->string('tabla_afectada');
            $table->string('descripcion', 500)->default('');
            $table->dateTime('fecha')->useCurrent();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['usuario_id', 'accion', 'tabla_afectada', 'fecha']);
        });

        Schema::create('intentos_acceso', function (Blueprint $table) {
            $table->string('email');
            $table->string('ip_address');
            $table->boolean('exitoso')->default(false);
            $table->string('mensaje', 500)->default('');
            $table->dateTime('fecha')->useCurrent();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['email', 'ip_address', 'fecha']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->dateTime('created_at')->useCurrent();
        });

        /*
        |--------------------------------------------------------------------------
        | TABLAS DEL FRAMEWORK (requeridas por Laravel para sesiones y caché)
        |--------------------------------------------------------------------------
        */

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->default(0)->index();
            $table->string('ip_address', 45)->default('');
            $table->text('user_agent');
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('intentos_acceso');
        Schema::dropIfExists('bitacora');
        Schema::dropIfExists('consultas_realizadas');
        Schema::dropIfExists('reportes_generados');
        Schema::dropIfExists('historial_saldos');
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('detalle_presupuestos');
        Schema::dropIfExists('presupuestos');
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('ingresos');
        Schema::dropIfExists('pagos_proveedores');
        Schema::dropIfExists('cuentas_pagar');
        Schema::dropIfExists('alertas_morosidad');
        Schema::dropIfExists('pagos_clientes');
        Schema::dropIfExists('cuentas_cobrar');
        Schema::dropIfExists('detalle_compras');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('envios_comprobantes');
        Schema::dropIfExists('anulaciones_facturas');
        Schema::dropIfExists('facturas_electronicas');
        Schema::dropIfExists('detalle_facturas');
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('detalle_asientos_contables');
        Schema::dropIfExists('asientos_contables');
        Schema::dropIfExists('catalogo_cuentas');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tipos_movimiento_inventario');
        Schema::dropIfExists('tipos_cuenta_contable');
        Schema::dropIfExists('tipos_comprobante');
        Schema::dropIfExists('estados');
        Schema::dropIfExists('metodos_pago');
        Schema::dropIfExists('tipos_gasto');
        Schema::dropIfExists('categorias_gastos');
        Schema::dropIfExists('categorias_productos');
        Schema::dropIfExists('permisos');
        Schema::dropIfExists('roles');
    }
};