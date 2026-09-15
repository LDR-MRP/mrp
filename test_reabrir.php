<?php
// Just a dummy to check if there is a typo in Lgs_envios.php
$content = file_get_contents('/home/christianguarneros/proyectos/mrp/Controllers/Lgs_envios.php');
if (php_check_syntax('/home/christianguarneros/proyectos/mrp/Controllers/Lgs_envios.php')) {
    echo "Syntax OK";
} else {
    echo "Syntax Error";
}
