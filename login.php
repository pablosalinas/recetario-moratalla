<?php
require_once __DIR__ . '/inc/auth.php';

// Si ya está logado, ir al recetario
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor, introduce usuario y contraseña.';
    } elseif (!attemptLogin($username, $password)) {
        $error = 'Usuario o contraseña incorrectos.';
        // Pequeña pausa para dificultar ataques de fuerza bruta
        sleep(1);
    } else {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — Recetario Tradicional Moratallero</title>
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
            width: 72px; height: 72px;
            background: linear-gradient(135deg, var(--terracotta), var(--wheat));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            box-shadow: 0 0 40px rgba(200, 113, 74, 0.3);
        }

        .login-emblem i { font-size: 2rem; color: #fff; }

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
    </style>
</head>
<body>
    <div class="login-wrapper">

        <header class="login-header">
            <div class="login-emblem">
                <i class="fa-solid fa-mortar-pestle"></i>
            </div>
            <h1 class="login-title">Recetario<br>de Moratalla</h1>
            <p class="login-subtitle">Patrimonio Gastronómico del Noroeste Murciano</p>
        </header>

        <div class="login-card">

            <?php if ($error): ?>
            <div class="error-banner" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm" novalidate>

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
            </form>

            <hr class="divider">

            <p class="login-footer">
                Acceso restringido al personal autorizado.<br>
                <a href="mailto:moratalla@example.com">¿Problemas para acceder?</a>
            </p>
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

        // Loading state al enviar
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
