<?php
include 'modelo/conexion.php'; // conexión a la base de datos

$edit_mode = false;
$empleado_data = []; // Array vacío para los datos del empleado
$page_title = "Registrar / Editar Empleado";
$form_action = "controlador/registro_empleados.php";

// Si se recibe un ID por la URL, activamos el modo de edición
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $edit_mode = true;
    $page_title = "Editar Empleado #" . intval($_GET['id']);
    $form_action = "controlador/actualizar_empleado.php";
    $id_empleado = intval($_GET['id']);

    // Consulta robusta para obtener todos los datos necesarios del empleado
    $sql = $conn->prepare("
        SELECT 
            e.*, 
            d.CALLE, d.NUMERO_EXTERIOR, d.NUMERO_INTERIOR, d.COLONIA, d.ID_MUNICIPIO,
            p.ID_PAIS,
            est.ID_ESTADO,
            (SELECT c.CORREO_EMPLEADO FROM correos c WHERE c.ID_EMPLEADO = e.ID_EMPLEADO AND c.TIPO_CORREO = 'principal' LIMIT 1) as correo_principal,
            (SELECT c.CORREO_EMPLEADO FROM correos c WHERE c.ID_EMPLEADO = e.ID_EMPLEADO AND c.TIPO_CORREO = 'secundario' LIMIT 1) as correo_secundario
        FROM empleados e
        LEFT JOIN domicilios d ON e.ID_EMPLEADO = d.ID_EMPLEADO
        LEFT JOIN municipios m ON d.ID_MUNICIPIO = m.ID_MUNICIPIO
        LEFT JOIN estados est ON m.ID_ESTADO = est.ID_ESTADO
        LEFT JOIN paises p ON est.ID_PAIS = p.ID_PAIS
        WHERE e.ID_EMPLEADO = ?
    ");
    $sql->bind_param("i", $id_empleado);
    $sql->execute();
    $result = $sql->get_result();
    if ($result->num_rows > 0) {
        $empleado_data = $result->fetch_assoc();
    } else {
        echo "<script>alert('No se encontró ningún empleado con el ID: " . $id_empleado . "'); window.location.href='index.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="user-solid-full.ico">
</head>
<body>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h1 class="h4 mb-0"><?= htmlspecialchars($page_title) ?></h1>
                        
                        <form action="index.php" method="GET" class="d-flex">
                            <input class="form-control me-2" type="search" placeholder="Buscar empleado por ID" aria-label="Buscar" name="id">
                            <button class="btn btn-light" type="submit">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </form>

                    </div>
                    <div class="card-body">
                        <?php if (!$edit_mode): ?>
                            <div class="alert alert-info">Para registrar un nuevo empleado, complete el formulario. Para editar, use el buscador superior.</div>
                        <?php endif; ?>

                        <form id="employeeForm" class="needs-validation" novalidate method="POST" action="<?= $form_action ?>">
                            
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="id_empleado" value="<?= htmlspecialchars($empleado_data['ID_EMPLEADO'] ?? '') ?>">
                            <?php endif; ?>

                            <?php if ($edit_mode): ?>
                                <h2 class="h5 border-bottom pb-2 mb-3">Detalles del Empleado</h2>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">ID de Empleado</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($empleado_data['ID_EMPLEADO'] ?? '') ?>" readonly style="background-color: #e9ecef;">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fecha_contratacion" class="form-label">Fecha de Contratación <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="fecha_contratacion" name="fecha_contratacion_empleado" value="<?= htmlspecialchars($empleado_data['FECHA_CONTRATACION'] ?? '') ?>" required>
                                        <div class="invalid-feedback">Por favor, seleccione una fecha de contratación.</div>
                                    </div>
                                </div>
                                <hr class="my-4">
                            <?php endif; ?>
                            
                            <h2 class="h5 border-bottom pb-2 mb-3">Datos Laborales</h2>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="contratante" class="form-label">Contratante (Jefe Directo)</label>
                                    <select class="form-select" id="contratante" name="contratante_empleado">
                                        <option value="">-- Sin Asignar --</option>
                                        <?php
                                            $sql_admins = $conn->query("SELECT e.ID_EMPLEADO, CONCAT(e.ID_EMPLEADO, ' - ', e.NOMBRE_EMPLEADO, ' ', e.APELLIDO_PATERNO) AS NOMBRE_COMPLETO FROM empleados e JOIN roles r ON e.ID_ROL = r.ID_ROL WHERE r.NOMBRE_ROL = 'Administrador' ORDER BY e.NOMBRE_EMPLEADO");
                                            while ($admin = $sql_admins->fetch_assoc()) {
                                                $selected = (isset($empleado_data['CONTRATANTE']) && $empleado_data['CONTRATANTE'] == $admin['ID_EMPLEADO']) ? 'selected' : '';
                                                echo "<option value='" . $admin['ID_EMPLEADO'] . "' $selected>" . htmlspecialchars($admin['NOMBRE_COMPLETO']) . "</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="departamento" class="form-label">Departamento <span class="text-danger">*</span></label>
                                    <select class="form-select" id="departamento" name="departamento_empleado" required>
                                        <option selected disabled value="">Seleccione...</option>
                                        <?php
                                            $sql_dept = $conn->query("SELECT ID_DEPARTAMENTO, NOMBRE_DEPARTAMENTO FROM departamentos ORDER BY NOMBRE_DEPARTAMENTO");
                                            while ($datos_dept = $sql_dept->fetch_assoc()) {
                                                $selected = (isset($empleado_data['ID_DEPARTAMENTO']) && $empleado_data['ID_DEPARTAMENTO'] == $datos_dept['ID_DEPARTAMENTO']) ? 'selected' : '';
                                                echo "<option value='" . $datos_dept['ID_DEPARTAMENTO'] . "' $selected>" . htmlspecialchars($datos_dept['NOMBRE_DEPARTAMENTO']) . "</option>";
                                            }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">Por favor, seleccione un departamento.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="rol_empleado" class="form-label">Rol del Empleado <span class="text-danger">*</span></label>
                                    <select id="rol_empleado" name="rol_empleado" class="form-select" required>
                                        <option value="" selected disabled>-- Seleccionar un rol --</option>
                                        <?php
                                        $sql_roles = "SELECT ID_ROL, NOMBRE_ROL FROM ROLES ORDER BY NOMBRE_ROL";
                                        $resultado_roles = $conn->query($sql_roles);
                                        while ($rol = $resultado_roles->fetch_assoc()) {
                                            $selected = (isset($empleado_data['ID_ROL']) && $empleado_data['ID_ROL'] == $rol['ID_ROL']) ? 'selected' : '';
                                            echo "<option value='" . $rol['ID_ROL'] . "' $selected>" . htmlspecialchars($rol['NOMBRE_ROL']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">Por favor, seleccione un rol.</div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h2 class="h5 border-bottom pb-2 mb-3">Información Personal</h2>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="nombre" class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre_empleado" value="<?= htmlspecialchars($empleado_data['NOMBRE_EMPLEADO'] ?? '') ?>" required maxlength="50" pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+">
                                    <div class="invalid-feedback">El nombre es obligatorio y solo debe contener letras y espacios.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="apellidoPaterno" class="form-label">Apellido Paterno</label>
                                    <input type="text" class="form-control" id="apellidoPaterno" name="apellido_paterno_empleado" value="<?= htmlspecialchars($empleado_data['APELLIDO_PATERNO'] ?? '') ?>" maxlength="50" pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]*">
                                    <div class="invalid-feedback">El apellido solo debe contener letras y espacios.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="apellidoMaterno" class="form-label">Apellido Materno</label>
                                    <input type="text" class="form-control" id="apellidoMaterno" name="apellido_materno_empleado" value="<?= htmlspecialchars($empleado_data['APELLIDO_MATERNO'] ?? '') ?>" maxlength="50" pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]*">
                                    <div class="invalid-feedback">El apellido solo debe contener letras y espacios.</div>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <label for="genero" class="form-label">Género <span class="text-danger">*</span></label>
                                    <select class="form-select" id="genero" name="genero_empleado" required>
                                        <option selected disabled value="">Seleccione...</option>
                                        <?php
                                            $sql_gen = $conn->query("SELECT ID_GENERO, NOMBRE_GENERO FROM generos ORDER BY NOMBRE_GENERO");
                                            while ($datos_gen = $sql_gen->fetch_assoc()) {
                                                $selected = (isset($empleado_data['ID_GENERO']) && $empleado_data['ID_GENERO'] == $datos_gen['ID_GENERO']) ? 'selected' : '';
                                                echo "<option value='" . $datos_gen['ID_GENERO'] . "' $selected>" . htmlspecialchars($datos_gen['NOMBRE_GENERO']) . "</option>";
                                            }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">Por favor, seleccione un género.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="curp" class="form-label">CURP <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" id="curp" name="curp_empleado" value="<?= htmlspecialchars($empleado_data['CURP_EMPLEADO'] ?? '') ?>" required minlength="18" maxlength="18" pattern="[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}">
                                    <div class="invalid-feedback">Ingrese un CURP válido (18 caracteres, formato oficial).</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="rfc" class="form-label">RFC <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" id="rfc" name="rfc_empleado" value="<?= htmlspecialchars($empleado_data['RFC_EMPLEADO'] ?? '') ?>" required minlength="13" maxlength="13" pattern="[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}">
                                    <div class="invalid-feedback">Ingrese un RFC válido (13 caracteres, formato oficial).</div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h2 class="h5 border-bottom pb-2 mb-3">Datos de Contacto</h2>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono_empleado" value="<?= htmlspecialchars($empleado_data['TELEFONO_EMPLEADO'] ?? '') ?>" required pattern="[0-9]{10}" maxlength="10">
                                    <div class="invalid-feedback">Ingrese un número de teléfono de 10 dígitos.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="correoPrincipal" class="form-label">Correo Principal</label>
                                    <input type="email" class="form-control" id="correoPrincipal" name="correo_principal_empleado" value="<?= htmlspecialchars($empleado_data['correo_principal'] ?? '') ?>" maxlength="100">
                                    <div class="invalid-feedback">Por favor, ingrese una dirección de correo válida.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="correoSecundario" class="form-label">Correo Secundario</label>
                                    <input type="email" class="form-control" id="correoSecundario" name="correo_secundario_empleado" value="<?= htmlspecialchars($empleado_data['correo_secundario'] ?? '') ?>" maxlength="100">
                                    <div class="invalid-feedback">Por favor, ingrese una dirección de correo válida.</div>
                                </div>
                            </div>
                            
                            <hr class="my-4">

                            <h2 class="h5 border-bottom pb-2 mb-3">Dirección</h2>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="calle" class="form-label">Calle <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="calle" name="calle_empleado" value="<?= htmlspecialchars($empleado_data['CALLE'] ?? '') ?>" required maxlength="100">
                                    <div class="invalid-feedback">La calle es obligatoria.</div>
                                </div>
                                <div class="col-md-2">
                                    <label for="numeroExterior" class="form-label">No. Exterior <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="numeroExterior" name="numero_exterior_empleado" value="<?= htmlspecialchars($empleado_data['NUMERO_EXTERIOR'] ?? '') ?>" required maxlength="10">
                                     <div class="invalid-feedback">El No. Ext. es obligatorio.</div>
                                </div>
                                <div class="col-md-2">
                                    <label for="numeroInterior" class="form-label">No. Interior</label>
                                    <input type="text" class="form-control" id="numeroInterior" name="numero_interior_empleado" value="<?= htmlspecialchars($empleado_data['NUMERO_INTERIOR'] ?? '') ?>" maxlength="10">
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-12">
                                    <label for="colonia" class="form-label">Colonia <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="colonia" name="colonia_empleado" value="<?= htmlspecialchars($empleado_data['COLONIA'] ?? '') ?>" required maxlength="100">
                                    <div class="invalid-feedback">La colonia es obligatoria.</div>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <label for="pais" class="form-label">País <span class="text-danger">*</span></label>
                                    <select class="form-select" id="pais" name="pais_empleado" required>
                                        <option value="">Seleccione un País...</option>
                                        <?php
                                            $sql_pais = $conn->query("SELECT ID_PAIS, NOMBRE_PAIS FROM paises WHERE ESTADO_PAIS = 1 ORDER BY NOMBRE_PAIS");
                                            while ($datos_pais = $sql_pais->fetch_assoc()) {
                                                $selected = (isset($empleado_data['ID_PAIS']) && $empleado_data['ID_PAIS'] == $datos_pais['ID_PAIS']) ? 'selected' : '';
                                                echo "<option value='" . $datos_pais['ID_PAIS'] . "' $selected>" . htmlspecialchars($datos_pais['NOMBRE_PAIS']) . "</option>";
                                            }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">Por favor, seleccione un país.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select" id="estado" name="estado_empleado" required <?= !$edit_mode ? 'disabled' : '' ?>>
                                        <option value="">Seleccione un Estado...</option>
                                        <?php
                                        if ($edit_mode && !empty($empleado_data['ID_PAIS'])) {
                                            $id_pais_sel = $empleado_data['ID_PAIS'];
                                            $stmt_est = $conn->prepare("SELECT ID_ESTADO, NOMBRE_ESTADO FROM estados WHERE ID_PAIS = ? AND ESTADO_ESTADO = 1 ORDER BY NOMBRE_ESTADO");
                                            $stmt_est->bind_param("i", $id_pais_sel);
                                            $stmt_est->execute();
                                            $sql_estados = $stmt_est->get_result();
                                            while ($datos_estado = $sql_estados->fetch_assoc()) {
                                                $selected = (isset($empleado_data['ID_ESTADO']) && $empleado_data['ID_ESTADO'] == $datos_estado['ID_ESTADO']) ? 'selected' : '';
                                                echo "<option value='" . $datos_estado['ID_ESTADO'] . "' $selected>" . htmlspecialchars($datos_estado['NOMBRE_ESTADO']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">Por favor, seleccione un estado.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="municipio" class="form-label">Municipio <span class="text-danger">*</span></label>
                                    <select class="form-select" id="municipio" name="municipio_empleado" required <?= !$edit_mode ? 'disabled' : '' ?>>
                                        <option value="">Seleccione un Municipio...</option>
                                        <?php
                                        if ($edit_mode && !empty($empleado_data['ID_ESTADO'])) {
                                            $id_estado_sel = $empleado_data['ID_ESTADO'];
                                            $stmt_muni = $conn->prepare("SELECT ID_MUNICIPIO, NOMBRE_MUNICIPIO FROM municipios WHERE ID_ESTADO = ? AND ESTADO_MUNICIPIO = 1 ORDER BY NOMBRE_MUNICIPIO");
                                            $stmt_muni->bind_param("i", $id_estado_sel);
                                            $stmt_muni->execute();
                                            $sql_muni = $stmt_muni->get_result();
                                            while ($datos_muni = $sql_muni->fetch_assoc()) {
                                                $selected = (isset($empleado_data['ID_MUNICIPIO']) && $empleado_data['ID_MUNICIPIO'] == $datos_muni['ID_MUNICIPIO']) ? 'selected' : '';
                                                echo "<option value='" . $datos_muni['ID_MUNICIPIO'] . "' $selected>" . htmlspecialchars($datos_muni['NOMBRE_MUNICIPIO']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">Por favor, seleccione un municipio.</div>
                                </div>
                            </div>
                            
                            <hr class="my-4">

                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($edit_mode): ?>
                                    <button type="button" class="btn btn-danger" onclick="confirmarEliminacion(<?= htmlspecialchars($id_empleado ?? 0) ?>)">
                                        <i class="fa-solid fa-trash"></i> Eliminar Empleado
                                    </button>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>

                                <div>
                                    <a href="index.php" class="btn btn-secondary">Limpiar / Nuevo</a>
                                    <button class="btn btn-primary" type="submit" name="<?= $edit_mode ? 'actualizar_empleado' : 'guardar_empleado' ?>" value="ok">
                                        <?= $edit_mode ? 'Actualizar Cambios' : 'Guardar Empleado' ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // --- SCRIPT DE VALIDACIÓN DE BOOTSTRAP ---
        (function () {
          'use strict'
          var forms = document.querySelectorAll('.needs-validation')
          Array.prototype.slice.call(forms)
            .forEach(function (form) {
              form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                  event.preventDefault()
                  event.stopPropagation()
                }
                form.classList.add('was-validated')
              }, false)
            })
        })();

        // --- FUNCIÓN PARA CONFIRMAR ELIMINACIÓN ---
        function confirmarEliminacion(id) {
            if (id > 0 && confirm("¿Estás realmente seguro de que deseas eliminar este empleado? Esta acción es irreversible.")) {
                window.location.href = "controlador/eliminar_empleado.php?id=" + id;
            }
        }

        // --- SCRIPT PARA UBICACIONES (PAÍS, ESTADO, MUNICIPIO) ---
        $(document).ready(function(){
            $('#pais').on('change', function(){
                var paisId = $(this).val();
                if(paisId){
                    $.ajax({
                        type: 'POST',
                        url: 'get_ubicaciones.php',
                        data: 'pais_id=' + paisId,
                        success: function(html){
                            $('#estado').html(html).prop('disabled', false); 
                            $('#municipio').html('<option value="">Seleccione un Municipio...</option>').prop('disabled', true);
                        }
                    }); 
                } else {
                    $('#estado').html('<option value="">Seleccione un Estado...</option>').prop('disabled', true);
                    $('#municipio').html('<option value="">Seleccione un Municipio...</option>').prop('disabled', true);
                }
            });

            $('#estado').on('change', function(){
                var estadoId = $(this).val();
                if(estadoId){
                    $.ajax({
                        type: 'POST',
                        url: 'get_ubicaciones.php',
                        data: 'estado_id=' + estadoId,
                        success: function(html){
                            $('#municipio').html(html).prop('disabled', false);
                        }
                    }); 
                } else {
                    $('#municipio').html('<option value="">Seleccione un Municipio...</option>').prop('disabled', true);
                }
            });
        });
    </script>
    
</body>
</html>