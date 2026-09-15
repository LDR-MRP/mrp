file_path = '/home/christianguarneros/proyectos/mrp/index.php'

with open(file_path, 'r') as f:
    content = f.read()

# Temporarily enable error display
content = content.replace(
    "ini_set('display_errors', 0);",
    "ini_set('display_errors', 1);"
)
content = content.replace(
    "error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR);",
    "error_reporting(E_ALL);"
)

with open(file_path, 'w') as f:
    f.write(content)
print("Debug mode enabled")
