# Análisis Técnico para Desarrolladores – Módulo de Logística
### Stack: Laravel (API REST) + React (Frontend) · TRUFoton

---

## 1. Base de Datos — Estructura Completa (Migraciones Laravel)

> Crear en este orden exacto por dependencias de FKs.

### 1.1 `proveedores` (reemplaza `trasladistas`)

```php
Schema::create('proveedores', function (Blueprint $table) {
    $table->id();
    $table->string('nombre_comercial');
    $table->string('razon_social');
    $table->string('rfc', 13)->unique();
    $table->enum('tipo_persona', ['Fisica', 'Moral']);
    $table->text('direccion_fiscal');
    $table->string('tipo_proveedor')->default('transporte');
    $table->string('correo_contacto')->nullable();
    $table->string('telefono_contacto', 20)->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();
    $table->index('rfc');
});
```

### 1.2 `choferes`

```php
Schema::create('choferes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
    $table->string('nombre_chofer');
    $table->string('licencia_chofer'); // path relativo al archivo subido
    $table->string('telefono_chofer', 20);
    $table->timestamps();
});
```

### 1.3 `madrinas`

```php
Schema::create('madrinas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
    $table->string('nombre_madrina');
    $table->string('placa_tracto', 15);
    $table->string('placa_caja', 15);
    $table->unsignedSmallInteger('capacidad_unidades');
    $table->string('num_eco', 30);
    $table->foreignId('id_creador')->constrained('users'); // Relacionado a la tabla users
    $table->timestamps();
});
```

### 1.3.1 `madrina_chofer_historial` (Historial de Choferes de Madrinas)

```php
Schema::create('madrina_chofer_historial', function (Blueprint $table) {
    $table->id();
    $table->foreignId('madrina_id')->constrained('madrinas')->onDelete('cascade');
    $table->foreignId('chofer_id')->constrained('choferes')->onDelete('cascade');
    $table->dateTime('fecha_inicio');
    $table->dateTime('fecha_fin')->nullable();
    $table->boolean('activo')->default(true); // true = chofer conduciendo actualmente la madrina
    $table->timestamps();
});
```

### 1.4 `envios`

```php
Schema::create('envios', function (Blueprint $table) {
    $table->id();
    $table->string('nombre_envio', 20)->unique(); // formato EN-000001
    $table->foreignId('id_motivo_movimiento')->constrained('tipo_motivos_movimientos');
    $table->foreignId('id_tipo_envio')->constrained('tipo_envios');
    $table->foreignId('proveedor_id')->constrained('proveedores');   // antes id_trasladista
    $table->foreignId('id_origen_envio')->nullable()->constrained('origenes_envios');
    $table->string('origen_abierto')->nullable();
    $table->foreignId('id_destino_envio')->nullable()->constrained('distribuidores');
    $table->string('destino_abierto')->nullable();
    $table->decimal('kilometraje_total', 8, 2)->default(0);
    $table->decimal('costo_total', 12, 2)->nullable();
    $table->date('fecha_tentativa_envio')->nullable();
    $table->date('fecha_tentativa_llegada')->nullable();
    $table->date('fecha_llegada')->nullable();
    $table->text('observaciones')->nullable();
    $table->foreignId('id_creador_envio')->constrained('usuarios');
    $table->unsignedTinyInteger('id_estado_envio')->default(1);
    // 1=Creado, 2=Asignado, 3=Aprobado, 4=Rechazado, 5=En tránsito, 6=Completado
    $table->timestamps();
});
```

### 1.5 `asignacion_envios_unidades`

```php
Schema::create('asignacion_envios_unidades', function (Blueprint $table) {
    $table->id();
    $table->foreignId('id_envio')->constrained('envios')->onDelete('cascade');
    $table->foreignId('id_unidad')->constrained('unidades');
    $table->timestamp('fecha_asignacion')->useCurrent();
    $table->decimal('costo_x_unidad', 12, 2)->nullable(); // calculado automáticamente
    $table->unsignedTinyInteger('id_estado_asignacion_envio')->default(1);
    $table->foreignId('id_asignador_envio')->constrained('usuarios');
    $table->unique(['id_envio', 'id_unidad']); // no duplicar asignaciones
    $table->timestamps();
});
```

### 1.6 `expedientes_aprobacion`

```php
Schema::create('expedientes_aprobacion', function (Blueprint $table) {
    $table->id();
    $table->string('nombre_expediente', 20)->unique(); // formato EX-000001
    $table->decimal('kilometraje_total', 10, 2)->nullable();
    $table->decimal('costo_total', 12, 2)->nullable();
    $table->date('fecha_expediente')->nullable();
    $table->unsignedTinyInteger('id_estado_expediente')->default(1);
    // 1=Creado, 2=Enviado a aprobación, 3=Regresado, 4=Rechazado, 5=Aprobado
    $table->foreignId('id_creador_expediente')->constrained('users'); // Relacionado a users
    $table->foreignId('id_aprobador')->nullable()->constrained('users'); // Relacionado a users
    $table->text('observaciones_creador')->nullable();
    $table->text('observaciones_aprobador')->nullable();
    $table->timestamps();
});
```

### 1.7 `asignacion_envios_expedientes`

```php
Schema::create('asignacion_envios_expedientes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('id_expediente')->constrained('expedientes_aprobacion')->onDelete('cascade');
    $table->foreignId('id_envio')->constrained('envios');
    $table->unique(['id_expediente', 'id_envio']);
    $table->timestamps();
});
```

### 1.8 `info_unidad_logistica`

```php
Schema::create('info_unidad_logistica', function (Blueprint $table) {
    $table->id('id_info_unidad_logistica');
    $table->foreignId('id_unidad')->constrained('unidades');
    $table->foreignId('id_envio')->nullable()->constrained('envios');
    $table->unsignedTinyInteger('id_estado_proceso')->default(1);
    // 1=En espera, 2=En proceso, 3=Finalizado
    $table->tinyInteger('carrocero')->default(0); // 1=Requiere carrocero
    $table->unsignedTinyInteger('id_estado_unidad_logistica')->nullable();
    $table->date('fecha_salida')->nullable();
    $table->date('fecha_llegada')->nullable();
    $table->timestamps();
});
```

### 1.9 `evidencias_logistica`

```php
Schema::create('evidencias_logistica', function (Blueprint $table) {
    $table->id('id_evidencia');
    $table->foreignId('id_info_unidad_logistica')
          ->constrained('info_unidad_logistica')->onDelete('cascade');
    $table->string('evidencia'); // nombre del archivo almacenado
    $table->tinyInteger('motivo_evidencia'); // 1=Salida, 2=Llegada
    $table->timestamps();
});
```

### 1.10 `asignacion_envio_choferes`

```php
Schema::create('asignacion_envio_choferes', function (Blueprint $table) {
    $table->id('id_asignacion_envio_chofer');
    $table->foreignId('id_envio')->constrained('envios')->onDelete('cascade');
    $table->foreignId('id_chofer')->constrained('choferes');
    $table->unique(['id_envio', 'id_chofer']);
    $table->timestamps();
});
```

### 1.11 `asignacion_envio_madrina`

```php
Schema::create('asignacion_envio_madrina', function (Blueprint $table) {
    $table->id('id_asignacion_envio_madrina');
    $table->foreignId('id_envio')->constrained('envios')->onDelete('cascade');
    $table->foreignId('id_madrina')->constrained('madrinas');
    $table->unique(['id_envio', 'id_madrina']);
    $table->timestamps();
});
```

### 1.12 `costos_trasladistas_tipo_unidades`

```php
Schema::create('costos_proveedores_tipo_unidades', function (Blueprint $table) {
    $table->id();
    $table->foreignId('proveedor_id')->constrained('proveedores');
    $table->foreignId('id_segmento')->constrained('segmentos');
    $table->foreignId('id_tipo_envio')->constrained('tipo_envios');
    $table->decimal('costo', 10, 2); // costo por km o por unidad
    $table->timestamps();
});
```

### 1.13 `info_logistica_interna` (Solo Origen Planta)

```php
Schema::create('info_logistica_interna', function (Blueprint $table) {
    $table->id('id_logistica_interna');
    $table->foreignId('id_unidad')->constrained('unidades');
    $table->tinyInteger('solicitado_entrega')->default(0); // 0=No, 1=Sí
    $table->unsignedTinyInteger('id_estado_entrega')->default(1); // 1=Pendiente, 2=Completada
    $table->timestamp('fecha_entrega_interna')->nullable();
    $table->timestamps();
});
```

### 1.14 `aprobadores_expedientes`

```php
Schema::create('aprobadores_expedientes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->timestamps();
});
```

---

## 2. Modelos Eloquent y Relaciones

```php
// Proveedor.php
class Proveedor extends Model {
    protected $table = 'proveedores';
    public function choferes() { return $this->hasMany(Chofer::class, 'proveedor_id'); }
    public function madrinas() { return $this->hasMany(Madrina::class, 'proveedor_id'); }
    public function envios()   { return $this->hasMany(Envio::class, 'proveedor_id'); }
}

// Chofer.php
class Chofer extends Model {
    protected $table = 'choferes';
    public function proveedor() { return $this->belongsTo(Proveedor::class, 'proveedor_id'); }
    public function historialMadrinas() { return $this->hasMany(MadrinaChoferHistorial::class, 'chofer_id'); }
}

// Madrina.php
class Madrina extends Model {
    protected $table = 'madrinas';
    public function proveedor() { return $this->belongsTo(Proveedor::class, 'proveedor_id'); }
    public function creador() { return $this->belongsTo(User::class, 'id_creador'); }
    public function historialChoferes() { return $this->hasMany(MadrinaChoferHistorial::class, 'madrina_id'); }
    public function choferActivo() { return $this->hasOne(MadrinaChoferHistorial::class, 'madrina_id')->where('activo', true); }
}

// MadrinaChoferHistorial.php
class MadrinaChoferHistorial extends Model {
    protected $table = 'madrina_chofer_historial';
    public function madrina() { return $this->belongsTo(Madrina::class, 'madrina_id'); }
    public function chofer() { return $this->belongsTo(Chofer::class, 'chofer_id'); }
}

// Envio.php
class Envio extends Model {
    public function proveedor() { return $this->belongsTo(Proveedor::class, 'proveedor_id'); }
    public function unidades()  { return $this->belongsToMany(Unidad::class, 'asignacion_envios_unidades', 'id_envio', 'id_unidad')
                                               ->withPivot('costo_x_unidad', 'fecha_asignacion'); }
    public function expedientes() { return $this->belongsToMany(ExpedienteAprobacion::class, 'asignacion_envios_expedientes', 'id_envio', 'id_expediente'); }
    public function choferes()  { return $this->belongsToMany(Chofer::class, 'asignacion_envio_choferes', 'id_envio', 'id_chofer'); }
    public function madrinas()  { return $this->belongsToMany(Madrina::class, 'asignacion_envio_madrina', 'id_envio', 'id_madrina'); }
}

// ExpedienteAprobacion.php
class ExpedienteAprobacion extends Model {
    protected $table = 'expedientes_aprobacion';
    public function envios()    { return $this->belongsToMany(Envio::class, 'asignacion_envios_expedientes', 'id_expediente', 'id_envio'); }
    public function creador()   { return $this->belongsTo(User::class, 'id_creador_expediente'); }
    public function aprobador() { return $this->belongsTo(User::class, 'id_aprobador'); }
}
```

---

## 3. Lógica de Negocio Crítica a Reimplementar en Laravel

### 3.1 Cálculo de Costo al Asignar Unidad a Envío

```php
// EnvioController::agregarUnidad()
public function agregarUnidad(Envio $envio, Request $request) {
    $unidad = Unidad::findOrFail($request->id_unidad);

    // 1. Obtener segmento de la unidad
    $segmento_id = $unidad->infoCompras->id_segmento;

    // 2. Buscar costo según segmento y tipo de envío
    $costoRow = CostosProveedorTipoUnidad::where('id_segmento', $segmento_id)
                ->where('id_tipo_envio', $envio->id_tipo_envio)->first();
    $costo_x_unidad = $costoRow ? $costoRow->costo * $envio->kilometraje_total : 0;

    // 3. Insertar asignación
    $envio->unidades()->syncWithoutDetaching([
        $unidad->id => ['costo_x_unidad' => $costo_x_unidad, 'fecha_asignacion' => now(), 'id_asignador_envio' => auth()->id()]
    ]);

    // 4. Recalcular costo total del envío
    $envio->costo_total = $envio->unidades()->sum('costo_x_unidad');
    $envio->save();

    // 5. Si el motivo de movimiento es TRASLADO CARROCERO (ID 6), marcar flag carrocero
    if ($envio->id_motivo_movimiento == 6) {
        InfoUnidadLogistica::updateOrCreate(
            ['id_unidad' => $unidad->id],
            ['carrocero' => 1, 'id_estado_proceso' => 1]
        );
    }

    return response()->json(['ok' => true, 'costo_total' => $envio->costo_total]);
}
```

### 3.2 Enviar Expediente a Aprobación

```php
// ExpedienteController::enviarAprobacion()
public function enviarAprobacion(ExpedienteAprobacion $expediente) {
    $expediente->update(['id_estado_expediente' => 2]);

    $aprobadores = AprobadorExpediente::with('usuario')->get(); // O tabla aprobadores_expedientes

    foreach ($aprobadores as $aprobador) {
        Mail::to($aprobador->usuario->email)
            ->send(new ExpedienteEnviadoMail($expediente, $aprobador->usuario));
    }

    return response()->json(['ok' => true]);
}
```

### 3.3 Aprobar Expediente

```php
// ExpedienteController::aprobar()
public function aprobar(ExpedienteAprobacion $expediente) {
    DB::transaction(function () use ($expediente) {
        $expediente->update(['id_estado_expediente' => 5, 'id_aprobador' => auth()->id()]);

        // Aprobar todos los envíos vinculados
        $expediente->envios()->update(['id_estado_envio' => 3]);

        // Notificar al creador
        Mail::to($expediente->creador->email)
            ->send(new ExpedienteAprobadoMail($expediente));
    });

    return response()->json(['ok' => true]);
}
```

### 3.4 Finalizar Unidad en Logística (Avanzar Subproceso)

```php
// LogisticaController::finalizarUnidad()
public function finalizarUnidad(Request $request) {
    $unidad    = Unidad::findOrFail($request->id_unidad);
    $id_origen = $unidad->id_origen;
    $subproceso = $unidad->id_sub_proceso;
    $carrocero = InfoUnidadLogistica::where('id_unidad', $unidad->id)->value('carrocero');

    $mapa = [
        // [id_origen][carrocero][sub_actual] => sub_siguiente
        1 => [
            1 => [7 => 38,  40 => 8],
            0 => [7 => 8],
        ],
        2 => [
            1 => [17 => 18, 20 => 21],
            0 => [17 => 21],
        ],
        3 => [
            1 => [30 => 31, 33 => 34],
            0 => [30 => 34],
        ],
    ];

    $sub_siguiente = $mapa[$id_origen][$carrocero][$subproceso] ?? null;

    if (!$sub_siguiente) {
        return response()->json(['error' => 'No se encontró transición válida'], 422);
    }

    $unidad->update(['id_sub_proceso' => $sub_siguiente]);

    return response()->json(['ok' => true, 'sub_siguiente' => $sub_siguiente]);
}
```

---

## 4. API Endpoints (`routes/api.php`)

```php
Route::middleware(['auth:sanctum'])->prefix('logistica')->group(function () {

    // ── Catálogos ──────────────────────────────────────────────
    Route::apiResource('proveedores', ProveedorController::class);
    Route::apiResource('choferes', ChoferController::class);
    Route::apiResource('madrinas', MadrinaController::class);
    Route::post('madrinas/{madrina}/asignar-chofer', [MadrinaController::class, 'asignarChofer']);

    // ── Bandeja de Unidades ────────────────────────────────────
    Route::get('unidades', [LogisticaController::class, 'index']);
    Route::post('unidades/{unidad}/solicitar', [LogisticaController::class, 'solicitar']);
    Route::post('unidades/{unidad}/cancelar-solicitud', [LogisticaController::class, 'cancelarSolicitud']);
    Route::post('unidades/{unidad}/solicitar-entrega-interna', [LogisticaController::class, 'solicitarEntregaInterna']); // Solo Planta
    Route::post('unidades/{unidad}/finalizar', [LogisticaController::class, 'finalizarUnidad']);

    // ── Evidencias ─────────────────────────────────────────────
    Route::post('unidades/{unidad}/evidencias', [EvidenciaController::class, 'subir']);
    Route::delete('evidencias/{evidencia}', [EvidenciaController::class, 'eliminar']);
    Route::get('unidades/{unidad}/evidencias', [EvidenciaController::class, 'index']);

    // ── Fechas Logísticas ──────────────────────────────────────
    Route::post('unidades/{unidad}/fechas', [LogisticaController::class, 'guardarFechas']);

    // ── Envíos ─────────────────────────────────────────────────
    Route::apiResource('envios', EnvioController::class);
    Route::post('envios/{envio}/unidades', [EnvioController::class, 'agregarUnidad']);
    Route::delete('envios/{envio}/unidades/{unidad}', [EnvioController::class, 'quitarUnidad']);
    Route::get('envios/{envio}/unidades-disponibles', [EnvioController::class, 'unidadesDisponibles']);
    Route::post('envios/{envio}/choferes', [EnvioController::class, 'asignarChofer']);
    Route::delete('envios/{envio}/choferes/{chofer}', [EnvioController::class, 'quitarChofer']);
    Route::post('envios/{envio}/madrinas', [EnvioController::class, 'asignarMadrina']);
    Route::delete('envios/{envio}/madrinas/{madrina}', [EnvioController::class, 'quitarMadrina']);

    // ── Expedientes de Aprobación ──────────────────────────────
    Route::apiResource('expedientes', ExpedienteController::class);
    Route::post('expedientes/{expediente}/envios', [ExpedienteController::class, 'agregarEnvio']);
    Route::delete('expedientes/{expediente}/envios/{envio}', [ExpedienteController::class, 'quitarEnvio']);
    Route::post('expedientes/{expediente}/enviar', [ExpedienteController::class, 'enviarAprobacion']);
    Route::post('expedientes/{expediente}/aprobar', [ExpedienteController::class, 'aprobar']);
    Route::post('expedientes/{expediente}/rechazar', [ExpedienteController::class, 'rechazar']);
    Route::post('expedientes/{expediente}/regresar', [ExpedienteController::class, 'regresar']);
    Route::get('expedientes/{expediente}/envios-disponibles', [ExpedienteController::class, 'enviosDisponibles']);
    Route::get('expedientes/aprobaciones', [ExpedienteController::class, 'aprobaciones']); // Solo enviados y regresados

    // ── Panel de Rutas ─────────────────────────────────────────
    Route::get('rutas/transito', [RutasController::class, 'obtenerRutasTransito']);
});
```

---

## 5. Form Requests (Validaciones)

### `StoreProveedorRequest`

```php
public function rules(): array {
    return [
        'nombre_comercial'  => 'required|string|max:255',
        'razon_social'      => 'required|string|max:255',
        'rfc'               => ['required', 'string', 'unique:proveedores,rfc', Rule::when(
            $this->tipo_persona === 'Moral',
            'regex:/^[A-Z&Ñ]{3}[0-9]{6}[A-Z0-9]{3}$/',
            'regex:/^[A-Z&Ñ]{4}[0-9]{6}[A-Z0-9]{3}$/'
        )],
        'tipo_persona'      => 'required|in:Fisica,Moral',
        'direccion_fiscal'  => 'required|string',
        'tipo_proveedor'    => 'required|string',
        'correo_contacto'   => 'nullable|email',
        'telefono_contacto' => 'nullable|string|max:20',
    ];
}
```

### `StoreChoferRequest`

```php
public function rules(): array {
    return [
        'proveedor_id'   => 'required|exists:proveedores,id',
        'nombre_chofer'  => 'required|string|max:255',
        'licencia_chofer'=> 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        'telefono_chofer'=> 'required|string|max:20',
    ];
}
```

### `StoreMadrinaRequest`

```php
public function rules(): array {
    return [
        'proveedor_id'        => 'required|exists:proveedores,id',
        'nombre_madrina'      => 'required|string|max:255',
        'placa_tracto'        => 'required|string|max:15',
        'placa_caja'          => 'required|string|max:15',
        'capacidad_unidades'  => 'required|integer|min:1|max:20',
        'num_eco'             => 'required|string|max:30',
        'chofer_id'           => 'nullable|exists:choferes,id', // Opcional al crear, representa la asignación inicial del chofer
    ];
}
```

### `StoreEnvioRequest`

```php
public function rules(): array {
    return [
        'id_motivo_movimiento'    => 'required|exists:tipo_motivos_movimientos,id',
        'id_tipo_envio'           => 'required|exists:tipo_envios,id',
        'proveedor_id'            => 'required|exists:proveedores,id',
        'fecha_tentativa_envio'   => 'required|date',
        'fecha_tentativa_llegada' => 'required|date|after:fecha_tentativa_envio',
        'kilometraje_total'       => 'required|numeric|min:0',
        'observaciones'           => 'nullable|string',
        // Origen: exclusión mutua entre id y texto abierto
        'id_origen_envio'         => 'nullable|exists:origenes_envios,id',
        'origen_abierto'          => 'nullable|string|required_without:id_origen_envio',
        'id_destino_envio'        => 'nullable|exists:distribuidores,id',
        'destino_abierto'         => 'nullable|string|required_without:id_destino_envio',
    ];
}
```

---

## 6. Estructura de Componentes React

```
src/
├── pages/
│   └── logistica/
│       ├── LogisticaDashboard.jsx       ← Bandeja principal con filtros y tabla
│       ├── LogisticaEnvios.jsx          ← Gestión de envíos individuales
│       ├── LogisticaExpedientes.jsx     ← Gestión de expedientes de aprobación
│       ├── LogisticaAprobaciones.jsx    ← Panel aprobador de expedientes
│       ├── LogisticaChoferes.jsx        ← CRUD choferes
│       ├── LogisticaMadrinas.jsx        ← CRUD madrinas
│       ├── LogisticaProveedores.jsx     ← CRUD proveedores fiscales
│       └── LogisticaPanelRutas.jsx      ← Mapa Google Maps
│
├── components/logistica/
│   ├── UnidadRow.jsx                   ← Fila de la tabla de unidades
│   ├── FiltrosBandeja.jsx              ← Panel de filtros (dropdown)
│   ├── ModalEditarUnidad.jsx           ← Modal de gestión de la unidad
│   ├── FormularioEvidencias.jsx        ← Carga y visualización de fotos/videos
│   ├── BotoneraSiguienteArea.jsx       ← Lógica del botón "Siguiente área"
│   ├── ProveedorFormModal.jsx          ← Formulario fiscal con validación RFC
│   ├── EnvioWizard.jsx                 ← Creador de envíos paso a paso
│   ├── ExpedienteCard.jsx              ← Tarjeta de resumen de expediente
│   └── MapTracker.jsx                  ← Mapa de rutas activas
│
├── hooks/
│   ├── useLogisticaUnidades.js         ← Fetching con filtros
│   ├── useEnvios.js
│   ├── useExpedientes.js
│   └── useProveedores.js
│
└── services/
    └── logisticaService.js             ← Axios wrapper para todos los endpoints
```

---

## 7. Lógica Especial del Frontend a Implementar

### 7.1 Validación de RFC según Tipo de Persona

```javascript
// utils/validaciones.js
export const validateRFC = (rfc, tipoPersona) => {
  const moralRegex  = /^[A-ZÑ&]{3}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[A-Z0-9]{3}$/;
  const fisicaRegex = /^[A-ZÑ&]{4}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[A-Z0-9]{3}$/;
  if (tipoPersona === 'Moral')  return moralRegex.test(rfc);
  if (tipoPersona === 'Fisica') return fisicaRegex.test(rfc);
  return false;
};
```

### 7.2 Habilitación del Botón "Siguiente Área"

```javascript
// utils/logistica.js
const subprocesosLogistica = {
  1: { conCarrocero: [7, 40], sinCarrocero: [7] },
  2: { conCarrocero: [17, 20], sinCarrocero: [17] },
  3: { conCarrocero: [30, 33], sinCarrocero: [30] },
};

export const puedeFinalizarLogistica = ({ id_origen, id_sub_proceso, carrocero, fecha_salida, fecha_llegada }) => {
  const tieneFechas = !!fecha_salida && !!fecha_llegada;
  if (!tieneFechas) return false;

  const config = subprocesosLogistica[id_origen];
  if (!config) return false;

  const subprocesosValidos = carrocero ? config.conCarrocero : config.sinCarrocero;
  return subprocesosValidos.includes(id_sub_proceso);
};
```

### 7.3 Conteo de Unidades Visible en Header

```javascript
// El badge "Unidades: N" se actualiza reactivamente desde el response de la API
const { data } = useLogisticaUnidades(filtros);
// data.meta.total → número de unidades filtradas
```

---

## 8. Almacenamiento de Archivos (Laravel Storage)

```php
// config: filesystems.php
// Disco 'logistica' apuntando a storage/app/logistica/

// Evidencias
Storage::disk('logistica')->put("evidencias/{$filename}", file_get_contents($request->file('evidencia')));

// Licencias de choferes
Storage::disk('logistica')->put("documentos/choferes/{$filename}", file_get_contents($request->file('licencia_chofer')));

// Exponer con enlace simbólico:
// php artisan storage:link
```

---

## 9. Notificaciones por Correo (Laravel Mail + PHPMailer → Mailables)

Crear 2 Mailables:

1. **`ExpedienteEnviadoMail`** — Se envía a todos los `aprobadores_expedientes`.
   - Contiene: nombre de expediente, nombre del creador, link a la pantalla de aprobación.

2. **`ExpedienteAprobadoMail`** — Se envía al creador del expediente.
   - Contiene: nombre de expediente, nombre del aprobador, observaciones, link al expediente.

Usar `Mail::to($email)->send(new ExpedienteEnviadoMail($expediente))` dentro de una **Job** (Cola) para no bloquear la respuesta de la API.

---

## 10. Tabla de Referencia: `id_proceso` → Área en el Sistema

| `id_proceso` | Área |
|---|---|
| 6 | Logística (Origen Puerto) |
| 13 | Logística (Origen Planta) |
| 20 | Logística (Origen Almacén) |

---

## 11. Lista de Tablas de Catálogos Requeridos (Sólo Lectura desde Logística)

| Tabla | Uso |
|---|---|
| `tipo_motivos_movimientos` | Select al crear envío (motivo del traslado) |
| `tipo_envios` | Select al crear envío (tipo de transporte) |
| `origenes_envios` | Select de origen del envío |
| `distribuidores` | Select de destino del envío |
| `segmentos` | Para calcular costos por tipo de unidad |
| `costos_proveedores_tipo_unidades` | Para calcular costo por km según segmento |
| `estados_expedientes` | Para mostrar badge de estado en expedientes |
| `estados_envios` | Para mostrar badge de estado en envíos |
| `estados_procesos` | Para colores y badges en tabla de unidades |
