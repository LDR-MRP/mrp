<?php 
	// spl_autoload_register(function($class){
	// 	if(file_exists("Libraries/".'Core/'.$class.".php")){
	// 		require_once("Libraries/".'Core/'.$class.".php");
	// 	}
	// });


	spl_autoload_register(function($class){

	$dirs = [
		'Libraries/Core/',
		'Requests/'
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