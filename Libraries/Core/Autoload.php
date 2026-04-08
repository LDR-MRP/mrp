<?php 

spl_autoload_register(function(string $class) {

	// --- 1. NUEVO: SOPORTE PARA NAMESPACES (Estándar PSR-4) ---
	// Si la clase contiene un '\', significa que usa Namespaces.
	// Ej: "Controllers\Api\V1\Usuarios" se convierte en "Controllers/Api/V1/Usuarios.php"
	if (str_contains($class, '\\')) {
		$file = str_replace('\\', '/', $class) . '.php';
		if (file_exists($file)) {
			require_once $file;
			return;
		}
	}

	// --- 2. CÓDIGO ORIGINAL: SOPORTE LEGACY (Clases sin namespace) ---
	$dirs = [
		'Libraries/Core/',
		'Requests/',
		'Services/',
		'Models/',
		'Factories/',
		'Interfaces/',
		'Enums/',
		'Processors/',
	];

	foreach ($dirs as $dir) {
		$file = $dir . $class . '.php';
		if (file_exists($file)) {
			require_once $file;
			return;
		}
	}

});

?>