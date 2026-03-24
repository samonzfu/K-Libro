<?php

/**
 * ==================== PROCESADOR DE AUTENTICACIÓN ====================
 * 
 * Este archivo PROCESA dos acciones principales:
 * 1. REGISTRO: Crear nueva cuenta de usuario
 * 2. LOGIN: Iniciar sesión con credenciales
 * 
 * Recibe datos via POST desde:
 * - frontend/1_Registro/registro.php (POST con accion='registro')
 * - frontend/2_Login/login.php (POST con accion='login')
 */

// ========== CONFIGURACIÓN DE ERRORES ==========
// Mostrar todos los errores en pantalla (útil durante desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ========== INICIAR SESIÓN ==========
// Habilitar uso de $_SESSION en todo el archivo
session_start();

// ========== INCLUIR ARCHIVOS NECESARIOS ==========
// Conexión a la base de datos MySQL
include '../conexionBD.php';
// Función para validar la fortaleza de la contraseña
include '../validadores/validar_contrasena.php';

/**
 * ==================== PROCESAMIENTO PRINCIPAL ==========
 * 
 * Verifica si se recibió un POST con la clave 'accion'
 * Si existe, procesamos el registro o login según el valor
 */
if(isset($_POST['accion'])){

    $accion = $_POST['accion'];

    /**
     * ========== ACCIÓN 1: REGISTRO DE NUEVO USUARIO ==========
     */
    if ($accion == 'registro'){
        
        // PASO 1: Obtener datos del formulario
        //         (nombre de usuario, correo, contraseña)
        $nombre = $_POST['nombre'];
        $contrasena = $_POST['contrasena'];
        $correo = $_POST['correo'];

        // PASO 2: VALIDAR LA CONTRASEÑA
        // Verificar que cumpla con los requisitos de seguridad
        // (8+ caracteres, mayúscula, minúscula, número, carácter especial)
        $validacion = validar_contrasena($contrasena);
        if(!$validacion['valida']){
            // Si la contraseña no es válida, mostrar errores en alert
            // y devolver al usuario a la página anterior
            $errores = implode('\n', $validacion['errores']);
            echo "<script>alert('Contraseña no segura:\\n\\n" . $errores . "'); window.history.back();</script>";
            exit;
        }

        // PASO 3: HASHEAR LA CONTRASEÑA
        // Convertir la contraseña en un hash seguro usando bcrypt
        // Ejemplo: "MiPass123!" se convierte en "$2y$10$...largaserie..."
        // Las contraseñas hasheadas NO se pueden revertir, solo verificar
        $contrasena = password_hash($contrasena, PASSWORD_DEFAULT);

        // PASO 4: COMPROBAR SI EL EMAIL YA EXISTE EN LA BBDD
        // Evitar usuarios duplicados con el mismo email
        $checkEmail = $pdo->prepare("SELECT email FROM usuarios WHERE email = ?");
        $checkEmail->execute([$correo]);
        $resultado = $checkEmail->fetchAll();
        if(count($resultado) > 0){
            // Email ya registrado - Mostrar error y salir
            echo "<script>alert('El email ya está registrado'); window.history.back();</script>";
            exit;
        }

        // PASO 5: INSERTAR NUEVO USUARIO EN LA BASE DE DATOS
        // Usar prepared statement (? son placeholders de seguridad)
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, contrasena) VALUES (?,?,?)");
        if ($stmt){
            // Ejecutar la query con los 3 valores: nombre, correo, contraseña hasheada
            if($stmt->execute([$nombre, $correo, $contrasena])){
                // ÉXITO: Usuario registrado correctamente
                // Redirigir a la página de login
                header('Location: ../../frontend/2_Login/login.php');
                exit;
            }else{
                // ERROR: Algo salió mal en la inserción
                echo "Error al registrar: " . htmlspecialchars($stmt->errorInfo()[2]);
            }
        }else{
            // ERROR: No se pudo preparar la query
            echo "Error al preparar la consulta.";
        }

    /**
     * ========== ACCIÓN 2: INICIO DE SESIÓN ==========
     */
    }elseif($accion == 'login'){
        
        // PASO 1: Obtener datos del formulario
        // trim() elimina espacios al inicio/final
        $nombre = trim($_POST['nombre'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        // PASO 2: Verificar que NO estén vacíos
        if ($nombre === '' || $contrasena === ''){
            echo "<script>alert('Por favor completa todos los campos'); window.history.back();</script>";
            exit;
        }

        // PASO 3: BUSCAR USUARIO EN LA BBDD
        // Buscar por nombre de usuario
        $stmt = $pdo->prepare("SELECT id, nombre, contrasena, rol FROM usuarios WHERE nombre = ?");
        if ($stmt) {
            $stmt->execute([$nombre]);
            $res = $stmt->fetchAll();
            
            // Verificar si existe el usuario
            if (count($res) > 0) {
                $row = $res[0];  // Obtener el primer (y único) resultado
                
                // PASO 4: VERIFICAR LA CONTRASEÑA
                // Comparar la contraseña ingresada con el hash guardado
                // password_verify() hace la comparación segura
                if (password_verify($contrasena, $row['contrasena'])) {
                    
                    // CONTRASEÑA CORRECTA - CREAR SESIÓN
                    // Guardar datos importantes en $_SESSION
                    $_SESSION['user_id'] = $row['id'];        // ID único del usuario
                    $_SESSION['nombre'] = $row['nombre'];      // Nombre de usuario
                    $_SESSION['rol'] = $row['rol'];            // Rol: 'user' o 'admin'
                    
                    // PASO 5: REDIRIGIR SEGÚN TIPO DE USUARIO
                    // Si es admin, ir al panel de administración
                    // Si es usuario normal, ir a la página de inicio
                    if ($row['rol'] === 'admin') {
                        header('Location: ../../frontend/7_Admin/panel_admin.php');
                    } else {
                        header('Location: ../../frontend/3_Inicio/inicio.php');
                    }
                    exit;
                } else {
                    // CONTRASEÑA INCORRECTA
                    echo "<script>alert('Usuario o contraseña incorrectos.'); window.history.back();</script>";
                    exit;
                }
            } else {
                // USUARIO NO ENCONTRADO
                echo "<script>alert('Usuario o contraseña incorrectos.'); window.history.back();</script>";
                exit;
            }
        } else {
            // ERROR EN EL SERVIDOR
            echo "Error en el servidor. Inténtalo más tarde.";
            exit;
        }
    }
}
?>