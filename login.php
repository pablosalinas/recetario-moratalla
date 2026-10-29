<?php
require_once __DIR__ . '/inc/auth.php';

// Si ya está logado, ir al recetario
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';
$showAccessBox = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? 'login';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($action === 'request_access') {
        $showAccessBox = true;
        $captcha  = trim($_POST['captcha'] ?? '');
        $honeypot = trim($_POST['website_hp'] ?? '');

        if (!empty($honeypot)) {
            $error = 'Solicitud rechazada por el filtro anti-robots.';
        } elseif (empty($username) || empty($password)) {
            $error = 'Debes rellenar el Usuario y la Contraseña en el formulario para enviar la solicitud.';
        } elseif ($captcha === '' || !isset($_SESSION['recetario_captcha_sum']) || (int)$captcha !== (int)$_SESSION['recetario_captcha_sum']) {
            $error = 'La respuesta del CAPTCHA de seguridad es incorrecta. Por favor, inténtalo de nuevo.';
        } else {
            $reqError = null;
            if (processAccessRequest($username, $password, $reqError)) {
                $success = "Solicitud enviada a info@moratalla-murcia.com. Tu usuario («{$username}») ha quedado registrado y permanece desactivado hasta que un administrador lo autorice.";
                $showAccessBox = false;
                $_POST['username'] = '';
                $_POST['password'] = '';
            } else {
                $error = $reqError ?: 'No se pudo procesar la solicitud de acceso.';
            }
        }
    } else {
        if (empty($username) || empty($password)) {
            $error = 'Por favor, introduce usuario y contraseña.';
        } elseif (!attemptLogin($username, $password)) {
            $anyUser = findAnyUserByUsername($username);
            if ($anyUser && !($anyUser['activo'] ?? true) && password_verify($password, $anyUser['password_hash'])) {
                $error = 'Tu cuenta está registrada pero desactivada. Está pendiente de autorización por un administrador.';
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
            // Pequeña pausa para dificultar ataques de fuerza bruta
            sleep(1);
        } else {
            header('Location: index.php');
            exit;
        }
    }
}

// Generar nuevo CAPTCHA anti-robots para la sesión
$_SESSION['recetario_captcha_n1']  = random_int(2, 12);
$_SESSION['recetario_captcha_n2']  = random_int(1, 9);
$_SESSION['recetario_captcha_sum'] = $_SESSION['recetario_captcha_n1'] + $_SESSION['recetario_captcha_n2'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — Recetario Tradicional Moratallero</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-deep:      #0f0e0d;
            --bg-card:      #1a1714;
            --bg-input:     #211e1a;
            --border:       rgba(210, 150, 80, 0.2);
            --terracotta:   #c8714a;
            --terracotta-h: #e08055;
            --wheat:        #d4a96a;
            --text-primary: #f0e6d3;
            --text-muted:   #8a7a68;
            --error-bg:     rgba(200, 60, 60, 0.15);
            --error-border: rgba(200, 60, 60, 0.4);
            --error-text:   #ff9090;
        }

        body {
            min-height: 100vh;
            background: var(--bg-deep);
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(200, 113, 74, 0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(212, 169, 106, 0.06) 0%, transparent 50%);
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Encabezado */
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-emblem {
            width: 110px; height: 110px;
            border-radius: 12px;
            overflow: hidden;
            margin: 0 auto 1.25rem;
            box-shadow: 0 0 40px rgba(212, 169, 106, 0.25), 0 4px 20px rgba(0,0,0,0.5);
            border: 1px solid rgba(212, 169, 106, 0.2);
            background: #fff;
        }

        .login-emblem img {
            width: 100%; height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
        }

        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.9rem;
            color: var(--text-primary);
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 0.875rem;
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }

        /* Tarjeta */
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.25rem;
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
        }

        /* Error */
        .error-banner {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            border-radius: 8px;
            padding: 0.875rem 1rem;
            color: var(--error-text);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1.5rem;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25%      { transform: translateX(-6px); }
            75%      { transform: translateX(6px); }
        }

        /* Campos */
        .form-group { margin-bottom: 1.25rem; }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--wheat);
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
            pointer-events: none;
            transition: color 0.2s;
        }

        .form-input {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.85rem 1rem 0.85rem 2.75rem;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input::placeholder { color: var(--text-muted); }

        .form-input:focus {
            border-color: var(--terracotta);
            box-shadow: 0 0 0 3px rgba(200, 113, 74, 0.15);
        }

        .form-input:focus + .input-icon { color: var(--terracotta); }

        /* Toggle contraseña */
        .toggle-pass {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 0.9rem;
            padding: 0;
            transition: color 0.2s;
        }
        .toggle-pass:hover { color: var(--wheat); }

        /* Botón */
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--terracotta), #b05c3a);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.03em;
            transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--terracotta-h), var(--terracotta));
            box-shadow: 0 8px 24px rgba(200, 113, 74, 0.35);
            transform: translateY(-1px);
        }

        .btn-login:active { transform: translateY(0); }

        /* Pie */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.78rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .login-footer a {
            color: var(--wheat);
            text-decoration: none;
        }

        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 1.5rem 0;
        }

        /* Enlace web principal */
        .main-site-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 1.25rem;
            padding: 0.55rem 1.1rem;
            border: 1px solid rgba(210, 150, 80, 0.3);
            border-radius: 30px;
            color: var(--wheat) !important;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none !important;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
            letter-spacing: 0.03em;
        }
        .main-site-link:hover {
            background: rgba(212, 169, 106, 0.1);
            border-color: rgba(212, 169, 106, 0.55);
            color: #f0e6d3 !important;
        }

        /* Banner Éxito */
        .success-banner {
            background: rgba(52, 211, 153, 0.12);
            border: 1px solid rgba(52, 211, 153, 0.4);
            border-radius: 8px;
            padding: 0.9rem 1rem;
            color: #6ee7b7;
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        .success-banner i {
            margin-top: 0.2rem;
            font-size: 1rem;
        }

        /* Panel ¿Problemas para acceder? + CAPTCHA */
        .access-request-box {
            margin-top: 1.25rem;
            padding: 1.15rem;
            background: rgba(33, 30, 26, 0.85);
            border: 1px dashed rgba(212, 169, 106, 0.35);
            border-radius: 10px;
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .access-request-box.active {
            display: block;
        }
        .access-request-info {
            font-size: 0.8rem;
            color: var(--text-primary);
            line-height: 1.5;
            margin-bottom: 0.9rem;
        }
        .access-request-info strong {
            color: var(--wheat);
        }
        .captcha-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.9rem;
        }
        .captcha-badge {
            background: #141210;
            border: 1px solid rgba(212, 169, 106, 0.35);
            border-radius: 8px;
            padding: 0.6rem 0.9rem;
            color: var(--wheat);
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.08em;
            white-space: nowrap;
            user-select: none;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }
        .captcha-input {
            flex: 1;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.65rem 0.85rem;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            outline: none;
            text-align: center;
        }
        .captcha-input:focus {
            border-color: var(--terracotta);
            box-shadow: 0 0 0 3px rgba(200, 113, 74, 0.15);
        }
        .btn-request-access {
            width: 100%;
            padding: 0.8rem 1rem;
            background: rgba(212, 169, 106, 0.15);
            border: 1px solid var(--wheat);
            border-radius: 8px;
            color: var(--wheat);
            font-family: 'Inter', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background 0.2s, color 0.2s, transform 0.1s;
        }
        .btn-request-access:hover {
            background: var(--wheat);
            color: #141210;
        }
        .problem-link-btn {
            background: none;
            border: none;
            color: var(--wheat);
            font-family: inherit;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: underline;
            text-underline-offset: 3px;
            padding: 0.2rem 0;
            transition: color 0.2s;
        }
        .problem-link-btn:hover {
            color: var(--terracotta-h);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">

        <header class="login-header">
            <div class="login-emblem">
                <img src="img/logo2.jpg" alt="Ilustración de Moratalla">
            </div>
            <h1 class="login-title">Recetario<br>de Moratalla</h1>
            <p class="login-subtitle">Patrimonio Gastronómico del Noroeste Murciano</p>
        </header>

        <div class="login-card">

            <?php if ($error): ?>
            <div class="error-banner" id="errorBanner" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span id="errorBannerText"><?= htmlspecialchars($error) ?></span>
            </div>
            <?php else: ?>
            <div class="error-banner" id="errorBanner" role="alert" style="display:none;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span id="errorBannerText"></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="success-banner" role="status">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm" novalidate>
                <input type="hidden" name="action" id="formAction" value="login">

                <div class="form-group">
                    <label class="form-label" for="username">Usuario</label>
                    <div class="input-wrapper">
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            placeholder="Tu nombre de usuario"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            autocomplete="username"
                            required
                            autofocus
                        >
                        <i class="fa-solid fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="••••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <i class="fa-solid fa-lock input-icon"></i>
                        <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar contraseña">
                            <i class="fa-solid fa-eye" id="togglePassIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Acceder al Recetario
                </button>

                <!-- Bloque de solicitud de acceso / problemas para acceder con CAPTCHA -->
                <div class="access-request-box <?= $showAccessBox ? 'active' : '' ?>" id="accessRequestBox">
                    <p class="access-request-info">
                        <i class="fa-solid fa-envelope-circle-check" style="color:var(--terracotta);"></i>
                        Rellena arriba tu <strong>Usuario</strong> y <strong>Contraseña</strong>. Se enviará un correo a <strong>info@moratalla-murcia.com</strong> y tu usuario quedará registrado (en estado <strong>desactivado</strong> hasta que un administrador lo autorice).
                    </p>

                    <!-- Honeypot anti-bots oculto -->
                    <div style="position:absolute; left:-9999px; opacity:0; pointer-events:none;" aria-hidden="true">
                        <label for="website_hp">No rellenar</label>
                        <input type="text" name="website_hp" id="website_hp" tabindex="-1" autocomplete="off">
                    </div>

                    <label class="form-label" for="captchaInput" style="margin-bottom:0.4rem;">
                        Verificación anti-robots (CAPTCHA) *
                    </label>
                    <div class="captcha-row">
                        <div class="captcha-badge" title="Resuelve la suma de seguridad">
                            <i class="fa-solid fa-shield-halved" style="color:var(--terracotta);"></i>
                            <?= (int)$_SESSION['recetario_captcha_n1'] ?> + <?= (int)$_SESSION['recetario_captcha_n2'] ?> = ?
                        </div>
                        <input
                            type="number"
                            id="captchaInput"
                            name="captcha"
                            class="captcha-input"
                            placeholder="Resultado"
                            autocomplete="off"
                        >
                    </div>

                    <button type="button" class="btn-request-access" id="btnSendAccessRequest">
                        <i class="fa-solid fa-paper-plane"></i>
                        Enviar datos y solicitar autorización
                    </button>
                </div>
            </form>

            <hr class="divider">

            <p class="login-footer">
                Acceso restringido al personal autorizado.<br>
                <button type="button" class="problem-link-btn" id="btnProblemasAcceso">
                    <i class="fa-solid fa-circle-question"></i> ¿Problemas para acceder?
                </button>
            </p>

            <div style="text-align:center; margin-top: 0.5rem;">
                <a href="https://www.moratalla-murcia.com" target="_blank" rel="noopener" class="main-site-link">
                    <i class="fa-solid fa-globe"></i>
                    www.moratalla-murcia.com
                </a>
            </div>
        </div>

    </div>

    <script>
        // Toggle visibilidad contraseña
        const toggleBtn = document.getElementById('togglePass');
        const passInput = document.getElementById('password');
        const passIcon  = document.getElementById('togglePassIcon');

        toggleBtn.addEventListener('click', () => {
            const isPass = passInput.type === 'password';
            passInput.type = isPass ? 'text' : 'password';
            passIcon.className = isPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });

        const loginForm        = document.getElementById('loginForm');
        const formAction       = document.getElementById('formAction');
        const userInput        = document.getElementById('username');
        const captchaInput     = document.getElementById('captchaInput');
        const accessBox        = document.getElementById('accessRequestBox');
        const btnProblemas     = document.getElementById('btnProblemasAcceso');
        const btnSendRequest   = document.getElementById('btnSendAccessRequest');
        const errorBanner      = document.getElementById('errorBanner');
        const errorBannerText  = document.getElementById('errorBannerText');

        function showClientError(msg) {
            errorBannerText.textContent = msg;
            errorBanner.style.display = 'flex';
            errorBanner.style.animation = 'none';
            void errorBanner.offsetWidth;
            errorBanner.style.animation = 'shake 0.4s ease';
        }

        function triggerAccessRequest() {
            const uVal = userInput.value.trim();
            const pVal = passInput.value;
            const cVal = captchaInput.value.trim();

            if (!uVal || !pVal) {
                showClientError('Debes rellenar primero los campos de Usuario y Contraseña en el formulario.');
                if (!uVal) userInput.focus();
                else passInput.focus();
                return;
            }

            if (!cVal) {
                showClientError('Por favor, resuelve el CAPTCHA de seguridad para evitar registros automáticos.');
                captchaInput.focus();
                return;
            }

            formAction.value = 'request_access';
            btnSendRequest.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando solicitud...';
            btnSendRequest.disabled = true;
            loginForm.submit();
        }

        // Al pulsar "¿Problemas para acceder?"
        btnProblemas.addEventListener('click', () => {
            if (!accessBox.classList.contains('active')) {
                accessBox.classList.add('active');
                if (!userInput.value.trim()) {
                    showClientError('Rellena tu Usuario y Contraseña en el formulario, resuelve el CAPTCHA y envía la solicitud.');
                    userInput.focus();
                } else if (!passInput.value) {
                    showClientError('Rellena la Contraseña en el formulario, resuelve el CAPTCHA y envía la solicitud.');
                    passInput.focus();
                } else {
                    captchaInput.focus();
                }
            } else {
                triggerAccessRequest();
            }
        });

        btnSendRequest.addEventListener('click', triggerAccessRequest);

        // Permitir enviar solicitud con Enter dentro del campo CAPTCHA
        captchaInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                triggerAccessRequest();
            }
        });

        // Loading state al enviar login normal
        loginForm.addEventListener('submit', function () {
            if (formAction.value === 'login') {
                const btn = document.getElementById('submitBtn');
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
                btn.disabled = true;
            }
        });
    </script>
</body>
</html>
