<?php

if (!empty($_POST['guardar_empleado']) && $_POST['guardar_empleado'] == 'ok') {

    include '../modelo/conexion.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // --- BLOQUE DE VALIDACIÓN DEL SERVIDOR ---
    $errors = [];

    // 1. Sanitizar y recoger datos clave
    $nombre             = isset($_POST['nombre_empleado']) ? trim($_POST['nombre_empleado']) : '';
    $apellido_paterno   = isset($_POST['apellido_paterno_empleado']) ? trim($_POST['apellido_paterno_empleado']) : '';
    $apellido_materno   = isset($_POST['apellido_materno_empleado']) ? trim($_POST['apellido_materno_empleado']) : '';
    $curp               = isset($_POST['curp_empleado']) ? strtoupper(trim($_POST['curp_empleado'])) : '';
    $rfc                = isset($_POST['rfc_empleado']) ? strtoupper(trim($_POST['rfc_empleado'])) : '';
    $telefono           = isset($_POST['telefono_empleado']) ? trim($_POST['telefono_empleado']) : '';
    $correo_principal   = isset($_POST['correo_principal_empleado']) ? trim($_POST['correo_principal_empleado']) : '';
    $correo_secundario  = isset($_POST['correo_secundario_empleado']) ? trim($_POST['correo_secundario_empleado']) : '';
    $calle              = isset($_POST['calle_empleado']) ? trim($_POST['calle_empleado']) : '';
    $numero_exterior    = isset($_POST['numero_exterior_empleado']) ? trim($_POST['numero_exterior_empleado']) : '';
    $colonia            = isset($_POST['colonia_empleado']) ? trim($_POST['colonia_empleado']) : '';

    // 2. Comprobar campos obligatorios
    if (empty($nombre)) $errors[] = "El nombre es obligatorio.";
    if (empty($curp)) $errors[] = "El CURP es obligatorio.";
    if (empty($rfc)) $errors[] = "El RFC es obligatorio.";
    if (empty($telefono)) $errors[] = "El teléfono es obligatorio.";
    if (empty($_POST['genero_empleado'])) $errors[] = "Debe seleccionar un género.";
    if (empty($_POST['departamento_empleado'])) $errors[] = "Debe seleccionar un departamento.";
    if (empty($_POST['rol_empleado'])) $errors[] = "Debe seleccionar un rol.";
    if (empty($calle)) $errors[] = "La calle es obligatoria.";
    if (empty($numero_exterior)) $errors[] = "El número exterior es obligatorio.";
    if (empty($colonia)) $errors[] = "La colonia es obligatoria.";
    if (empty($_POST['pais_empleado'])) $errors[] = "Debe seleccionar un país.";
    if (empty($_POST['estado_empleado'])) $errors[] = "Debe seleccionar un estado.";
    if (empty($_POST['municipio_empleado'])) $errors[] = "Debe seleccionar un municipio.";
    if (empty($apellido_paterno) && empty($apellido_materno)) $errors[] = "Debe proporcionar al menos un apellido.";

    // 3. Validar formatos específicos
    if (!empty($nombre) && !preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', $nombre)) $errors[] = "El nombre solo puede contener letras y espacios.";
    if (!empty($apellido_paterno) && !preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]*$/', $apellido_paterno)) $errors[] = "El apellido paterno solo puede contener letras y espacios.";
    if (!empty($apellido_materno) && !preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]*$/', $apellido_materno)) $errors[] = "El apellido materno solo puede contener letras y espacios.";
    
    if (!empty($curp) && !preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/', $curp)) $errors[] = "El formato del CURP no es válido.";
    if (!empty($rfc) && !preg_match('/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/', $rfc)) $errors[] = "El formato del RFC no es válido.";
    if (!empty($telefono) && !preg_match('/^[0-9]{10}$/', $telefono)) $errors[] = "El teléfono debe contener 10 dígitos numéricos.";
    
    if (!empty($correo_principal) && !filter_var($correo_principal, FILTER_VALIDATE_EMAIL)) $errors[] = "El correo principal no es una dirección de email válida.";
    if (!empty($correo_secundario) && !filter_var($correo_secundario, FILTER_VALIDATE_EMAIL)) $errors[] = "El correo secundario no es una dirección de email válida.";


    // 4. Si hay errores, detener el proceso y notificar al usuario
    if (!empty($errors)) {
        $error_message = implode("\\n", $errors); // Usamos \\n para saltos de línea en el alert de JS
        echo "<script>
                alert('Por favor corrija los siguientes errores:\\n\\n" . $error_message . "');
                window.history.back();
              </script>";
        exit;
    }
    // --- FIN DEL BLOQUE DE VALIDACIÓN ---


    $conn->begin_transaction();
    try {
        // --- Recoger el resto de los datos (ya validados o no críticos) ---
        $contratante        = !empty($_POST['contratante_empleado']) ? $_POST['contratante_empleado'] : null;
        $genero             = $_POST['genero_empleado'];
        $departamento       = $_POST['departamento_empleado'];
        $id_rol             = $_POST['rol_empleado'];
        $numero_interior    = trim($_POST['numero_interior_empleado']);
        $municipio          = $_POST['municipio_empleado'];
        $fecha_contratacion = date('Y-m-d');
        
        // --- 3. PRIMERO: Insertar empleado ---
        $sql_empleado = "INSERT INTO empleados (NOMBRE_EMPLEADO, APELLIDO_PATERNO, APELLIDO_MATERNO, ID_GENERO, CURP_EMPLEADO, RFC_EMPLEADO, TELEFONO_EMPLEADO, CONTRATANTE, FECHA_CONTRATACION, ID_DEPARTAMENTO, ID_ROL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_empleado = $conn->prepare($sql_empleado);
        $stmt_empleado->bind_param("sssisssisii", $nombre, $apellido_paterno, $apellido_materno, $genero, $curp, $rfc, $telefono, $contratante, $fecha_contratacion, $departamento, $id_rol);
        $stmt_empleado->execute();
        $id_empleado_insertado = $conn->insert_id;

        // --- 4. SEGUNDO: Insertar domicilio usando el ID del nuevo empleado ---
        $sql_domicilio = "INSERT INTO domicilios (ID_EMPLEADO, CALLE, NUMERO_EXTERIOR, NUMERO_INTERIOR, COLONIA, ID_MUNICIPIO) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_domicilio = $conn->prepare($sql_domicilio);
        $stmt_domicilio->bind_param("issssi", $id_empleado_insertado, $calle, $numero_exterior, $numero_interior, $colonia, $municipio);
        $stmt_domicilio->execute();
        
        // --- 5. Insertar correos ---
        if (!empty($correo_principal)) {
            $sql_correo_p = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, 'principal')";
            $stmt_correo_p = $conn->prepare($sql_correo_p);
            $stmt_correo_p->bind_param("is", $id_empleado_insertado, $correo_principal);
            $stmt_correo_p->execute();
        }

        if (!empty($correo_secundario)) {
            $sql_correo_s = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, 'secundario')";
            $stmt_correo_s = $conn->prepare($sql_correo_s);
            $stmt_correo_s->bind_param("is", $id_empleado_insertado, $correo_secundario);
            $stmt_correo_s->execute();
        }

        // Confirmar cambios
        $conn->commit();
        echo "<script>alert('Empleado registrado exitosamente.'); window.location.href='../index.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        // Usamos json_encode para escapar caracteres especiales en el mensaje de error para JavaScript
        $error_message = json_encode("Error de sistema: " . $e->getMessage());
        echo "<script>alert('Error al registrar empleado: ' + $error_message); window.history.back();</script>";
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo "<script>alert('Acceso no autorizado.'); window.location.href='../index.php';</script>";
}
?>