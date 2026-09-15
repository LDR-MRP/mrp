import os

file_path = '/home/christianguarneros/proyectos/mrp/Controllers/Lgs_planeaciones.php'
with open(file_path, 'r') as f:
    content = f.read()

if "require_once" not in content[:200]:
    content = content.replace("<?php", "<?php\nrequire_once('Services/ApiResponser.php');\nrequire_once('Services/Lgs_planeacionesService.php');\nrequire_once('Models/Lgs_planeacionesModel.php');\n")

with open(file_path, 'w') as f:
    f.write(content)
