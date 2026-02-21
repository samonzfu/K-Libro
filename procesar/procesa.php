<?php
// Conexión a la BBDD.
include '../backend/conexionBD.php';
// Incluir función de validación de contraseña
include '../back/Validadores/validar_contrasena.php';

// VERIFICAR DATOS RECIBIDOS:
if(isset($_POST['accion'])){

    $accion = $_POST['accion'];

    // REGISTRO:
    if ($accion == 'registro'){
        // Ahora recogemos datos:
        $nombre = $_POST['nombre'];
        $contrasena = $_POST['contrasena'];
        $correo = $_POST['correo'];

        // VALIDAR CONTRASEÑA:
        $validacion = validar_contrasena($contrasena);
        if(!$validacion['valida']){
            // Si la contaseña no es válida, devovlemos el error detallado.
            $errores = implode('\n', $validacion['errores']);
            echo "<script>alert('Contraseña no segura:\\n\\n" . $errores . "'); window.history.back();</script>";
            exit;
        }

        // Hashear la contraseña después de validarla:
        $contrasena = password_hash($contrasena, PASSWORD_DEFAULT);

        // Comprobar si el email ya existe:
        $checkEmail = $conexion->prepare("SELECT email FROM usuarios WHERE email = ?");
        $checkEmail->bind_param("s", $correo);
        $checkEmail->execute();
        $resultado = $checkEmail->get_result();
        if($resultado->num_rows > 0){
            echo "<script>alert('El email ya está registrado'); window.history.back();</script>";
            exit;
        }
        $checkEmail->close();

        // Usar prepared statement para insertar de forma segura.
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, contrasena) VALUES (?,?,?)");
        if ($stmt){
            $stmt->bind_param("sss", $nombre, $correo, $contrasena);
            if($stmt->execute()){
                // Registro exitoso - Redirigir al login:
                header('Location: ../frontend/2. Login/login.html');
                exit;
            }else{
                echo "Error al registrar: " .htmlspecialchars($conexion->error);
            }
            $stmt->close();
        }else{
            echo "Error al preparar la consulta: " . htmlspecialchars($conexion->error);
        }
    }

}



?>