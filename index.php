<?php
require_once __DIR__ . '/inc/auth.php';
requireLogin();
$user = getCurrentUser();
$isAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetario Tradicional Moratallero - Patrimonio Gastronómico</title>
    <meta name="description" content="Explora y preserva las 155 recetas tradicionales de Moratalla a partir de los libros históricos de 1992 y 2001. Sabores de la huerta, la sierra y la tradición del Noroeste murciano.">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* ── Barra de usuario logado ───────────────────────────────── */
        .user-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 9999;
            background: rgba(15,14,13,0.96);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(200,113,74,0.2);
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 1.5rem;
            gap: 1rem;
            font-size: 0.78rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .user-bar-name {
            color: #8a7a68;
        }
        .user-bar-name strong { color: #d4a96a; }
        .user-bar-role {
            background: rgba(200,113,74,0.15);
            color: #c8714a;
            border: 1px solid rgba(200,113,74,0.3);
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        .user-bar-role.admin-badge {
            background: rgba(212,169,106,0.15);
            color: #d4a96a;
            border-color: rgba(212,169,106,0.3);
        }
        .user-bar a {
            color: #8a7a68;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            transition: color 0.2s;
            white-space: nowrap;
        }
        .user-bar a:hover { color: #d4a96a; }
        .user-bar .btn-admin {
            background: linear-gradient(135deg, #c8714a, #b05c3a);
            color: #fff !important;
            border-radius: 6px;
            padding: 4px 12px;
            font-weight: 600;
        }
        .user-bar .btn-admin:hover {
            background: linear-gradient(135deg, #e08055, #c8714a);
        }
        /* Empujar el header original hacia abajo por la user-bar */
        body { padding-top: 40px; }
        .main-header { top: 40px !important; }
    </style>
</head>
<body class="rustic-dark-theme">

    <!-- Barra de usuario -->
    <div class="user-bar" id="user-bar">
        <span class="user-bar-name">
            <i class="fa-solid fa-user"></i>
            Hola, <strong><?= htmlspecialchars($user['nombre']) ?></strong>
        </span>
        <span class="user-bar-role <?= $isAdmin ? 'admin-badge' : '' ?>">
            <?= $isAdmin ? '⚙ Administrador' : 'Consulta' ?>
        </span>
        <?php if ($isAdmin): ?>
        <a href="admin/index.php" class="btn-admin">
            <i class="fa-solid fa-screwdriver-wrench"></i> Panel Admin
        </a>
        <?php endif; ?>
        <a href="https://www.moratalla-murcia.com" target="_blank" rel="noopener" title="Web principal de Moratalla">
            <i class="fa-solid fa-globe"></i> moratalla-murcia.com
        </a>
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Salir
        </a>
    </div>

    <!-- Dynamic Header & Navigation -->
    <header class="main-header" id="main-header">
        <div class="header-container">
            <a href="#" class="logo-area">
                <span class="logo-accent">Moratalla</span>
                <span class="logo-text">Sabores de Antaño</span>
            </a>
            
            <nav class="nav-links" id="main-nav">
                <a href="#recetario-section" class="active-link"><i class="fa-solid fa-utensils"></i> Recetas</a>
                <a href="#acerca-section"><i class="fa-solid fa-book-open"></i> El Libro</a>
                <a href="#comunidad-section"><i class="fa-solid fa-seedling"></i> Tradición</a>
            </nav>
            
            <div class="header-actions">
                <button class="theme-toggle" id="theme-toggle-btn" aria-label="Cambiar tema">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Showcase Section -->
    <section class="hero-showcase" id="hero-section" style="background-image: linear-gradient(rgba(18, 18, 18, 0.75), rgba(18, 18, 18, 0.95)), url('img/hero-bg.png');">
        <div class="hero-content">
            <span class="hero-tagline"><i class="fa-solid fa-mortar-pestle"></i> Legado Gastronómico</span>
            <h1 class="hero-title">Recetario Tradicional Moratallero</h1>
            <p class="hero-description">
                Un viaje en el tiempo a través de las cocinas, las lumbre y las orzas de Moratalla. Digitalización de las 155 recetas tradicionales originales recolectadas de la sabiduría popular a partir de las publicaciones históricas de 1992 y 2001.
            </p>
            <div class="hero-stats">
                <div class="stat-card">
                    <span class="stat-number">155</span>
                    <span class="stat-label">Recetas Históricas</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">7</span>
                    <span class="stat-label">Categorías</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">1992-2001</span>
                    <span class="stat-label">Años de Recopilación</span>
                </div>
            </div>
        </div>
        
        <div class="scroll-indicator">
            <span>Explorar Recetario</span>
            <i class="fa-solid fa-chevron-down animate-bounce"></i>
        </div>
    </section>

    <!-- Main Content Container -->
    <main class="content-wrapper" id="recetario-section">
        
        <!-- Interactive Search & Filtering Area -->
        <section class="control-panel" id="filters-area">
            <div class="search-bar-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="recipe-search-input" placeholder="Buscar por receta, ingrediente, facilitador..." aria-label="Buscar receta">
                <button class="clear-search-btn" id="clear-search-btn" aria-label="Limpiar búsqueda">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Categories Slider Grid -->
            <div class="categories-container-title">
                <h2>Categorías del Recetario</h2>
            </div>
            
            <div class="categories-slider" id="categories-tabs" role="tablist">
                <button class="category-card active" data-category="all" role="tab" aria-selected="true">
                    <div class="category-icon-wrapper"><i class="fa-solid fa-bowl-food"></i></div>
                    <span class="category-name">Todas las Recetas</span>
                </button>
                <button class="category-card" data-category="Mojos y Entrantes" role="tab" aria-selected="false">
                    <div class="category-image" style="background-image: url('img/cat-entradas.png');"></div>
                    <span class="category-name">Mojos y Entrantes</span>
                </button>
                <button class="category-card" data-category="Sopas y Caldos" role="tab" aria-selected="false">
                    <div class="category-image" style="background-image: url('img/cat-sopas.png');"></div>
                    <span class="category-name">Sopas y Caldos</span>
                </button>
                <button class="category-card" data-category="Arroces y Harinas" role="tab" aria-selected="false">
                    <div class="category-image" style="background-image: url('img/cat-arroces.png');"></div>
                    <span class="category-name">Arroces y Harinas</span>
                </button>
                <button class="category-card" data-category="Potajes y Guisos" role="tab" aria-selected="false">
                    <div class="category-image" style="background-image: url('img/cat-potajes.png');"></div>
                    <span class="category-name">Potajes y Guisos</span>
                </button>
                <button class="category-card" data-category="Carnes" role="tab" aria-selected="false">
                    <div class="category-image" style="background-image: url('img/cat-carnes.png');"></div>
                    <span class="category-name">Carnes</span>
                </button>
                <button class="category-card" data-category="Fritos y Pescados" role="tab" aria-selected="false">
                    <div class="category-image" style="background-image: url('img/cat-pescados.png');"></div>
                    <span class="category-name">Fritos y Pescados</span>
                </button>
                <button class="category-card" data-category="Repostería" role="tab" aria-selected="false">
                    <div class="category-image" style="background-image: url('img/cat-reposteria.png');"></div>
                    <span class="category-name">Repostería</span>
                </button>
            </div>
        </section>

        <!-- Recipes Showcase Section -->
        <section class="recipes-display-wrapper">
            <div class="recipes-meta-summary">
                <span class="results-counter" id="recipes-counter-text">Cargando recetario...</span>
            </div>
            
            <div class="recipes-grid" id="recipes-grid-container">
                <!-- Recipe cards dynamically populated by app.js -->
            </div>
            
            <!-- Empty State -->
            <div class="no-recipes-found hidden" id="no-recipes-element">
                <i class="fa-solid fa-mortar-pestle-empty empty-icon"></i>
                <h3>No hemos encontrado recetas</h3>
                <p>Prueba con otros términos de búsqueda o cambia la categoría seleccionada.</p>
            </div>
        </section>
    </main>

    <!-- Side Panel / Details Drawer -->
    <aside class="recipe-drawer" id="recipe-detail-drawer" aria-hidden="true">
        <div class="drawer-overlay" id="drawer-close-overlay"></div>
        <div class="drawer-body">
            <button class="close-drawer-btn" id="close-drawer-btn" aria-label="Cerrar detalles">
                <i class="fa-solid fa-arrow-right-long"></i>
            </button>
            <div class="drawer-scroll-content" id="drawer-inner-content">
                <!-- Recipe detailed view dynamically populated -->
            </div>
        </div>
    </aside>

    <!-- Footer -->
    <footer class="app-footer" id="page-footer">
        <div class="footer-container">
            <div class="footer-info">
                <h3>Patrimonio Inmaterial de Moratalla</h3>
                <p>Una iniciativa local para la salvaguarda de la gastronomía y saberes rurales tradicionales del Noroeste murciano.</p>
            </div>
            <div class="footer-links">
                <a href="#main-header">Inicio</a>
                <a href="#recetario-section">Recetas</a>
                <a href="https://www.moratalla-murcia.com" target="_blank" rel="noopener"><i class="fa-solid fa-globe"></i> moratalla-murcia.com</a>
                <?php if ($isAdmin): ?>
                <a href="admin/index.php"><i class="fa-solid fa-screwdriver-wrench"></i> Admin</a>
                <?php endif; ?>
                <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Recetario de Moratalla. Digitalizado con rigor a partir de las obras originales de 1992 y 2001.</p>
        </div>
    </footer>

    <!-- Datos de sesión para el JS -->
    <script>
        window.APP_USER = <?= json_encode([
            'nombre'   => $user['nombre'],
            'rol'      => $user['rol'],
            'isAdmin'  => $isAdmin,
        ]) ?>;
    </script>

    <!-- App Logic Script -->
    <script src="js/app_v2.js"></script>
</body>
</html>
