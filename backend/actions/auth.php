<?php
/**
 * Procesa el registro y login de usuarios.
 * Recibe POST con campo 'accion' = 'registro' | 'login'.
 */

// Cookie de sesión más segura: sin acceso desde JavaScript y sin enviarse en peticiones cruzadas
$appRoot = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')));
$cookiePath = rtrim($appRoot, '/') . '/';
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => $cookiePath,
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../validators/validar_contrasena.php';
require_once __DIR__ . '/../helpers/flash.php';

/** Longitud en caracteres (usa mbstring si está disponible; algunos hostings no lo traen). */
function auth_longitud(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto) : strlen($texto);
}

/** Vuelve a login/registro mostrando un aviso (y conservando lo escrito, salvo la contraseña). */
function auth_volver(string $pagina, string $es, string $en, array $viejo = []): void
{
    flash_set('error', $es, $en);
    $_SESSION['flash_old'] = $viejo;
    header('Location: ../../public/pages/' . $pagina . '/' . $pagina . '.php');
    exit;
}

if(isset($_POST['accion'])){

    $accion = $_POST['accion'];

    /**
     * ========== ACCIÓN 1: REGISTRO DE NUEVO USUARIO ==========
     */
    if ($accion == 'registro'){
        
        // PASO 1: Obtener datos del formulario
        //         (nombre de usuario, correo, contraseña)
        $nombre = trim($_POST['nombre'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $correo = trim($_POST['correo'] ?? '');

        // Validar campos básicos (los límites coinciden con la tabla usuarios)
        $viejo = ['nombre' => $nombre, 'correo' => $correo];
        if ($nombre === '' || $correo === '' || $contrasena === '') {
            auth_volver('registro', 'Completa todos los campos.', 'Please fill in all fields.', $viejo);
        }
        if (auth_longitud($nombre) > 50) {
            auth_volver('registro', 'El nombre de usuario no puede superar los 50 caracteres.', 'The username cannot be longer than 50 characters.', $viejo);
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || auth_longitud($correo) > 100) {
            auth_volver('registro', 'Introduce un correo electrónico válido.', 'Enter a valid email address.', $viejo);
        }

        // PASO 2: VALIDAR LA CONTRASEÑA
        // Verificar que cumpla con los requisitos de seguridad
        // (8+ caracteres, mayúscula, minúscula, número, carácter especial)
        $validacion = validar_contrasena($contrasena);
        if(!$validacion['valida']){
            // Si la contraseña no es válida, avisar y devolver al usuario al formulario
            auth_volver('registro', 'La contraseña no cumple los requisitos de seguridad.', 'The password does not meet the security requirements.', $viejo);
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
            // Email ya registrado - Avisar y volver al formulario
            auth_volver('registro', 'Ya existe una cuenta con ese correo electrónico.', 'An account with that email address already exists.', $viejo);
        }

        // PASO 5: INSERTAR NUEVO USUARIO EN LA BASE DE DATOS
        // Usar prepared statement (? son placeholders de seguridad)
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, contrasena) VALUES (?,?,?)");
        if ($stmt){
            // Ejecutar la query con los 3 valores: nombre, correo, contraseña hasheada
            if($stmt->execute([$nombre, $correo, $contrasena])){
                // ÉXITO: Usuario registrado correctamente
                // Redirigir a la página de login con un aviso
                flash_set('success', 'Cuenta creada correctamente. Ya puedes iniciar sesión.', 'Account created successfully. You can now log in.');
                header('Location: ../../public/pages/login/login.php');
                exit;
            }
        }
        // ERROR: no se pudo crear la cuenta
        auth_volver('registro', 'No se pudo crear la cuenta. Inténtalo de nuevo.', 'The account could not be created. Please try again.', $viejo);

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
            auth_volver('login', 'Introduce tu usuario y tu contraseña.', 'Enter your username and password.', ['nombre' => $nombre]);
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
                    // Nuevo ID de sesión tras el login (evita la fijación de sesión)
                    session_regenerate_id(true);
                    unset($_SESSION['csrf_token']);
                    // Guardar datos importantes en $_SESSION
                    $_SESSION['user_id'] = $row['id'];        // ID único del usuario
                    $_SESSION['nombre'] = $row['nombre'];      // Nombre de usuario
                    $_SESSION['rol'] = $row['rol'];            // Rol: 'user' o 'admin'
                    
                    // PASO 5: REDIRIGIR SEGÚN TIPO DE USUARIO
                    // Si es admin, ir al panel de administración
                    // Si es usuario normal, ir a la página de inicio
                    if (in_array($row['rol'], ['admin', 'moderador'], true)) {
                        header('Location: ../../public/pages/admin/panel_admin.php');
                    } else {
                        header('Location: ../../public/pages/inicio/inicio.php');
                    }
                    exit;
                } else {
                    // CONTRASEÑA INCORRECTA
                    auth_volver('login', 'Usuario o contraseña incorrectos.', 'Incorrect username or password.', ['nombre' => $nombre]);
                }
            } else {
                // USUARIO NO ENCONTRADO
                auth_volver('login', 'Usuario o contraseña incorrectos.', 'Incorrect username or password.', ['nombre' => $nombre]);
            }
        } else {
            // ERROR EN EL SERVIDOR
            auth_volver('login', 'Error en el servidor. Inténtalo más tarde.', 'Server error. Please try again later.', ['nombre' => $nombre]);
        }
    }
}
?>