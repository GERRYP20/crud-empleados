<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Empleados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" type="image/x-icon" href="user-solid-full.ico">
</head>
<body>

    <div class="container mt-5 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">Lista de Empleadosss</h1>
            <a href="index.php" class="btn btn-primary">Agregar Nuevo Empleado</a>
        </div>

        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <form class="row g-2" onsubmit="buscarEmpleado(event)">
                    <div class="col-auto">
                        <input type="number" id="id_buscar" class="form-control" placeholder="ID de empleado" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fa-solid fa-magnifying-glass"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Departamento</th>
                                <th scope="col">Teléfono</th>
                                <th scope="col">Correo principal</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Incluimos la conexión al inicio
                            include 'modelo/conexion.php';
                            
                            // Verificamos si se está realizando una búsqueda
                            if (isset($_GET['buscar_id']) && !empty(trim($_GET['buscar_id']))) {

                                $id_buscado = $_GET['buscar_id'];

                                // ⚠️ Usamos una consulta preparada para evitar inyección SQL
                                $sql = $conn->prepare("SELECT ID_EMPLEADO, NOMBRE_EMPLEADO, APELLIDO_PATERNO, APELLIDO_MATERNO, TELEFONO_EMPLEADO, NOMBRE_DEPARTAMENTO, CORREO_EMPLEADO 
                                    FROM empleados 
                                    JOIN departamentos USING(ID_DEPARTAMENTO) 
                                    JOIN correos USING(ID_EMPLEADO) 
                                    WHERE TIPO_CORREO = 'principal' AND ID_EMPLEADO = ?");
                                
                                // Asociamos el ID buscado al '?' de la consulta
                                $sql->bind_param("i", $id_buscado); // "i" significa que es un entero
                                $sql->execute();
                                $resultado = $sql->get_result();

                                if ($resultado->num_rows > 0) {
                                    // Si hay resultados, los mostramos
                                    while ($datos = $resultado->fetch_assoc()) { ?>
                                        <tr>
                                            <td><?= $datos['ID_EMPLEADO'] ?></td>
                                            <td><?php echo ucwords(strtolower($datos['NOMBRE_EMPLEADO'] . " " . $datos['APELLIDO_PATERNO'] . " " . $datos['APELLIDO_MATERNO'])); ?></td>
                                            <td><?= $datos['NOMBRE_DEPARTAMENTO'] ?></td>
                                            <td><?= $datos['TELEFONO_EMPLEADO'] ?></td>
                                            <td><?= $datos['CORREO_EMPLEADO'] ?></td>
                                            <td class="text-center">
                                                <a href="index.php?id=<?= $datos['ID_EMPLEADO'] ?>" class="btn btn-primary btn-sm me-2"><i class="fa-solid fa-pen-to-square"></i></a>
                                                <button class="btn btn-danger btn-sm" onclick="confirmarEliminacion(<?= $datos['ID_EMPLEADO'] ?>)"><i class="fa-solid fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php }
                                } else {
                                    // Si no hay resultados, mostramos un mensaje
                                    echo '<tr><td colspan="6" class="text-center">No se encontraron empleados con ese ID.</td></tr>';
                                }

                            } else {
                                // Mensaje inicial, antes de cualquier búsqueda
                                echo '<tr><td colspan="6" class="text-center">Ingresa un ID para buscar un empleado.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmarEliminacion(id) {
            if (confirm("¿Estás seguro de que deseas eliminar este empleado? Esta acción no se puede deshacer.")) {
                window.location.href = "controlador/eliminar_empleado.php?id=" + id;
            }
        }

        function buscarEmpleado(event) {
            event.preventDefault(); // Evita que se recargue la página por el método GET del form
            const id = document.getElementById("id_buscar").value;
            if (id) {
                // Redirigimos a la misma página, pero con un parámetro de búsqueda
                window.location.href = "lista-usuarios.php?buscar_id=" + id;
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>