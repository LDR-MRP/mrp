import re

def update_file(path, old, new):
    with open(path, 'r') as f:
        content = f.read()
    content = re.sub(old, new, content)
    with open(path, 'w') as f:
        f.write(content)
    print(f"Updated {path}")

# Lgs_envios.php
update_file(
    '/home/christianguarneros/proyectos/mrp/Controllers/Lgs_envios.php',
    r'\$puedeReabrir = \(\$estadoActual === 4 \|\| \$estadoActual === 8 \|\| \(\$estadoActual === 2 && \$estadoPlan < 3\)\);',
    r'$puedeReabrir = ($estadoActual === 4 || $estadoActual === 8 || ($estadoActual === 2 && $estadoPlan < 2));'
)

# detalle.php
update_file(
    '/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/detalle.php',
    r'\$enPlaneacionAbierta = \(\$estadoEnvio === 2 && \$estadoPlan < 3\);',
    r'$enPlaneacionAbierta = ($estadoEnvio === 2 && $estadoPlan < 2);'
)

