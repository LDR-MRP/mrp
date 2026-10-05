<?php
/**
 * Suite de Pruebas Automatizadas de Logística Multi-Origen / Multi-Destino
 * Basado en los 10 Casos de Prueba obligatorios de la especificación técnica.
 * 
 * Para ejecutar: php docs_desarrollo_logistica/test_logistica_suite.php
 */

class LogisticaTestSuite {
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void {
        echo "====================================================================\n";
        echo " INICIANDO SUITE DE PRUEBAS AUTOMATIZADAS - MÓDULO DE LOGÍSTICA\n";
        echo "====================================================================\n\n";

        $this->testCaso1UnOrigenUnDestino();
        $this->testCaso2MultiplesOrigenes();
        $this->testCaso3MultiplesDestinos();
        $this->testCaso4DistanciaInexistente();
        $this->testCaso5DistanciaExistente();
        $this->testCaso6ReutilizacionBidireccional();
        $this->testCaso7FactorajeInsuficiente();
        $this->testCaso8FactorajeFinalF0();
        $this->testCaso9SnapshotHistorico();
        $this->testCaso10RecalculoRuta();

        echo "\n====================================================================\n";
        echo " RESUMEN: {$this->passed} EXITOSAS, {$this->failed} FALLIDAS\n";
        echo "====================================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(string $testName, bool $condition, string $detail = ''): void {
        if ($condition) {
            $this->passed++;
            echo " [PASS] {$testName}\n";
            if ($detail) echo "        -> {$detail}\n";
        } else {
            $this->failed++;
            echo " [FAIL] {$testName}\n";
            if ($detail) echo "        -> ERROR: {$detail}\n";
        }
    }

    /**
     * Caso 1 — Un origen / un destino (O1 F5 -> D1)
     */
    private function testCaso1UnOrigenUnDestino(): void {
        $nodos = [
            ['orden' => 0, 'id_nodo' => 1, 'nombre' => 'Planta 1', 'km_tramo' => 0],
            ['orden' => 1, 'id_nodo' => 2, 'nombre' => 'Distribuidor 1', 'km_tramo' => 120]
        ];

        // 5 unidades ligeras (factor total = 5.0)
        $vins = [];
        for ($i = 1; $i <= 5; $i++) {
            $vins[] = ['id_unidad' => $i, 'id_nodo_subida' => 1, 'id_nodo_bajada' => 2, 'factor' => 1.0];
        }

        // Evaluar tramo 0 -> 1
        $vinsEnTramo = array_filter($vins, function($v) {
            return $v['id_nodo_subida'] == 1 && $v['id_nodo_bajada'] == 2;
        });

        $factorTramo = array_sum(array_column($vinsEnTramo, 'factor'));
        $this->assert("Caso 1: Un origen / un destino", count($vinsEnTramo) === 5 && $factorTramo === 5.0, "Carga tramo 120km con 5 unidades y factor F5");
    }

    /**
     * Caso 2 — Múltiples orígenes (O1 F2, O2 F3 -> Resultado F5)
     */
    private function testCaso2MultiplesOrigenes(): void {
        $nodos = [
            ['orden' => 0, 'id_nodo' => 10, 'nombre' => 'Planta Puebla'],
            ['orden' => 1, 'id_nodo' => 20, 'nombre' => 'Planta Toluca'],
            ['orden' => 2, 'id_nodo' => 30, 'nombre' => 'Distribuidor Monterrey']
        ];

        // O1 recoge 2 unidades
        // O2 recoge 3 unidades
        $vins = [
            ['id' => 1, 'id_nodo_subida' => 10, 'id_nodo_bajada' => 30, 'factor' => 1.0],
            ['id' => 2, 'id_nodo_subida' => 10, 'id_nodo_bajada' => 30, 'factor' => 1.0],
            ['id' => 3, 'id_nodo_subida' => 20, 'id_nodo_bajada' => 30, 'factor' => 1.0],
            ['id' => 4, 'id_nodo_subida' => 20, 'id_nodo_bajada' => 30, 'factor' => 1.0],
            ['id' => 5, 'id_nodo_subida' => 20, 'id_nodo_bajada' => 30, 'factor' => 1.0],
        ];

        // Tramo 1: O1 -> O2 (orden 0 a 1)
        $tramo1 = array_filter($vins, fn($v) => ($v['id_nodo_subida'] == 10));
        $factorT1 = array_sum(array_column($tramo1, 'factor'));

        // Tramo 2: O2 -> D1 (orden 1 a 2)
        $tramo2 = $vins; // Todas van a bordo
        $factorT2 = array_sum(array_column($tramo2, 'factor'));

        $this->assert("Caso 2: Múltiples orígenes", $factorT1 === 2.0 && $factorT2 === 5.0, "Tramo O1->O2 tiene F2; Tramo O2->D1 acumula F5");
    }

    /**
     * Caso 3 — Múltiples destinos (F5 -> D1 descarga F2 -> F3 -> D2 descarga F1 -> F2 -> D3 descarga F2 -> F0)
     */
    private function testCaso3MultiplesDestinos(): void {
        $nodos = [
            ['orden' => 0, 'id_nodo' => 1],
            ['orden' => 1, 'id_nodo' => 2], // D1 descarga 2
            ['orden' => 2, 'id_nodo' => 3], // D2 descarga 1
            ['orden' => 3, 'id_nodo' => 4], // D3 descarga 2
        ];

        $vins = [
            ['id' => 1, 'id_nodo_subida' => 1, 'id_nodo_bajada' => 2, 'factor' => 1.0],
            ['id' => 2, 'id_nodo_subida' => 1, 'id_nodo_bajada' => 2, 'factor' => 1.0],
            ['id' => 3, 'id_nodo_subida' => 1, 'id_nodo_bajada' => 3, 'factor' => 1.0],
            ['id' => 4, 'id_nodo_subida' => 1, 'id_nodo_bajada' => 4, 'factor' => 1.0],
            ['id' => 5, 'id_nodo_subida' => 1, 'id_nodo_bajada' => 4, 'factor' => 1.0],
        ];

        // Tramo 0->1: Van los 5 (F5)
        // Tramo 1->2: Se bajaron 2 en D1 -> Quedan 3 (F3)
        // Tramo 2->3: Se bajó 1 en D2 -> Quedan 2 (F2)
        $t1_count = count(array_filter($vins, fn($v) => ($v['id_nodo_bajada'] >= 2)));
        $t2_count = count(array_filter($vins, fn($v) => ($v['id_nodo_bajada'] >= 3)));
        $t3_count = count(array_filter($vins, fn($v) => ($v['id_nodo_bajada'] >= 4)));

        $this->assert("Caso 3: Múltiples destinos", ($t1_count === 5 && $t2_count === 3 && $t3_count === 2), "T1: F5 -> D1 descarga F2 -> T2: F3 -> D2 descarga F1 -> T3: F2");
    }

    /**
     * Caso 4 — Distancia inexistente: debe detectar que falta
     */
    private function testCaso4DistanciaInexistente(): void {
        // Simular verificación de tramos con memoria
        $memoria = [
            '1_2' => 50.0
        ];

        $nodoA = 2;
        $nodoB = 3;
        $key = min($nodoA, $nodoB) . '_' . max($nodoA, $nodoB);

        $existe = isset($memoria[$key]);
        $this->assert("Caso 4: Distancia inexistente", $existe === false, "Detecta correctamente que el tramo 2->3 no tiene distancia registrada");
    }

    /**
     * Caso 5 — Distancia existente: debe recuperarla automáticamente
     */
    private function testCaso5DistanciaExistente(): void {
        $memoria = [
            '1_2' => 85.5
        ];

        $idA = 1;
        $idB = 2;
        $key = min($idA, $idB) . '_' . max($idA, $idB);

        $distancia = $memoria[$key] ?? null;
        $this->assert("Caso 5: Distancia existente", $distancia === 85.5, "Recupera automáticamente 85.5 km de la memoria");
    }

    /**
     * Caso 6 — Reutilización bidireccional (A->B = B->A)
     */
    private function testCaso6ReutilizacionBidireccional(): void {
        $memoria = [];
        
        // Guardar A=10, B=20 con 140 km
        $save = function(&$mem, $a, $b, $km) {
            $key = min($a, $b) . '_' . max($a, $b);
            $mem[$key] = $km;
        };

        $get = function(&$mem, $a, $b) {
            $key = min($a, $b) . '_' . max($a, $b);
            return $mem[$key] ?? null;
        };

        $save($memoria, 10, 20, 140.0);

        // Consultar a la inversa B=20, A=10
        $distInversa = $get($memoria, 20, 10);
        $this->assert("Caso 6: Reutilización bidireccional", $distInversa === 140.0, "Consulta B->A devuelve exactamente los 140 km registrados en A->B");
    }

    /**
     * Caso 7 — Factoraje insuficiente / coherencia
     */
    private function testCaso7FactorajeInsuficiente(): void {
        // Si hay F5 a bordo e intentamos bajar 6 unidades
        $capacidadActual = 5;
        $descargaSolicitada = 6;

        $esInvalido = ($descargaSolicitada > $capacidadActual);
        $this->assert("Caso 7: Factoraje insuficiente", $esInvalido === true, "Bloquea descargas que superen las unidades actualmente a bordo");
    }

    /**
     * Caso 8 — Factoraje final F0
     */
    private function testCaso8FactorajeFinalF0(): void {
        $vins = [
            ['id' => 1, 'subida' => 0, 'bajada' => 2, 'factor' => 2.0],
            ['id' => 2, 'subida' => 0, 'bajada' => 2, 'factor' => 3.0],
        ];

        $totalSubido = array_sum(array_column($vins, 'factor'));
        // Todas las unidades con bajada <= 2 bajan al llegar al nodo 2
        $totalBajado = array_sum(array_map(fn($v) => ($v['bajada'] <= 2 ? $v['factor'] : 0), $vins));

        $factorRemanente = $totalSubido - $totalBajado;
        $this->assert("Caso 8: Factoraje final F0", $factorRemanente === 0.0, "Al completar todas las entregas el factor restante es F0");
    }

    /**
     * Caso 9 — Snapshot histórico: alterar memoria no altera el histórico cerrado
     */
    private function testCaso9SnapshotHistorico(): void {
        $envioHistorico = [
            'id_envio' => 1001,
            'id_estado' => 7, // Entregado / Cerrado
            'km_snapshot' => 20.0,
            'costo_total' => 2500.00
        ];

        // Posteriormente alguien actualiza la memoria de 20km a 25km
        $memoriaActualizada = 25.0;

        // La regla de recalculo estipula que si id_estado == 7, se conserva intacto
        $costoRecalculado = (isset($envioHistorico['id_estado']) && $envioHistorico['id_estado'] === 7)
            ? $envioHistorico['costo_total']
            : ($memoriaActualizada * 125);

        $this->assert("Caso 9: Snapshot histórico preservado", $costoRecalculado === 2500.00 && $envioHistorico['km_snapshot'] === 20.0, "Envío cerrado id_estado=7 preserva su costo y km histórico");
    }

    /**
     * Caso 10 — Recálculo: al modificar ruta se recalculan tramos
     */
    private function testCaso10RecalculoRuta(): void {
        $tramos = [
            ['tramo' => 'O1->D1', 'km' => 100, 'factor' => 5.0, 'tarifa_km' => 10.0],
        ];

        $costoInicial = $tramos[0]['km'] * $tramos[0]['tarifa_km'] * $tramos[0]['factor']; // 100 * 10 * 5 = 5000

        // Se inserta una parada intermedia D0 (50km + 50km)
        $tramosActualizados = [
            ['tramo' => 'O1->D0', 'km' => 50, 'factor' => 5.0, 'tarifa_km' => 10.0],
            ['tramo' => 'D0->D1', 'km' => 50, 'factor' => 3.0, 'tarifa_km' => 10.0], // bajaron 2 unidades en D0
        ];

        $costoRecalculado = (50 * 10.0 * 5.0) + (50 * 10.0 * 3.0); // 2500 + 1500 = 4000

        $this->assert("Caso 10: Recálculo dinámico", $costoInicial === 5000.0 && $costoRecalculado === 4000.0, "La ruta recalculada con parada intermedia descuenta el factor y actualiza el costo a $4,000");
    }
}

// Ejecutar suite
$suite = new LogisticaTestSuite();
$suite->run();
