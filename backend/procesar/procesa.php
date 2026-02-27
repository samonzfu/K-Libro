<?php
// debug: mostrar todos los errores en pantalla
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();   // si hace falta

// Conexión a la BBDD.
include '../conexionBD.php';
// Incluir función de validación de contraseña
include '../Validadores/validar_contrasena.php';

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
                // Registro exitoso - Redirigir al login (carpeta renombrada a 2_Login):
                header('Location: ../../frontend/2_Login/login.php');
                exit;
            }else{
                echo "Error al registrar: " .htmlspecialchars($conexion->error);
            }
            $stmt->close();
        }else{
            echo "Error al preparar la consulta: " . htmlspecialchars($conexion->error);
        }

    // LOGIN
    }elseif($accion == 'login'){
        // Recogemos y saneamos datos del formulario del login:
        $nombre = trim($_POST['nombre'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if ($nombre === '' || $contrasena === ''){
            echo "<script>alert('Por favor completa todos los campos'); window.history.back();</script>";
            exit;
        }

        // Buscar usuario por nombre y comprobar contraseña:
        $stmt = $conexion->prepare("SELECT id, nombre, contrasena FROM usuarios WHERE nombre = ?");
        if ($stmt) {
            $stmt->bind_param("s", $nombre);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                if (password_verify($contrasena, $row['contrasena'])) {
                    session_start();
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['nombre'] = $row['nombre'];
                    header('Location: ../../frontend/3_Inicio/inicio.php');
                    exit;
                } else {
                    echo "<script>alert('Usuario o contraseña incorrectos.'); window.history.back();</script>";
                    exit;
                }
            } else {
                echo "<script>alert('Usuario o contraseña incorrectos.'); window.history.back();</script>";
                exit;
            }
            $stmt->close();
        } else {
            echo "Error en el servidor. Inténtalo más tarde.";
            exit;
        }
    }

}
?>