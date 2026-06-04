<?php
require_once __DIR__ . '/../inc/auth.php';
requireAdmin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin — Recetario Moratalla</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="fa-solid fa-mortar-pestle"></i></div>
            <div>
                <div class="brand-name">Recetario</div>
                <div class="brand-sub">Panel Admin</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item active">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>
            <a href="categorias.php" class="nav-item">
                <i class="fa-solid fa-layer-group"></i> Categorías
            </a>
            <a href="recetas.php" class="nav-item">
                <i class="fa-solid fa-utensils"></i> Recetas
            </a>
            <a href="usuarios.php" class="nav-item">
                <i class="fa-solid fa-users"></i> Usuarios
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-icon"><i class="fa-solid fa-user"></i></div>
                <div>
                    <div class="sidebar-user-name"><?= htmlspecialchars($user['nombre']) ?></div>
                    <div class="sidebar-user-role">Administrador</div>
                </div>
            </div>
            <a href="../index.php" class="btn-sidebar-action">
                <i class="fa-solid fa-arrow-left"></i> Ver Recetario
            </a>
            <a href="../logout.php" class="btn-sidebar-action btn-danger-soft">
                <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Dashboard</h1>
                <p class="admin-page-sub">Bienvenido al panel de administración del Recetario</p>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card-admin loading">
                <div class="stat-icon stat-icon-amber"><i class="fa-solid fa-utensils"></i></div>
                <div class="stat-body">
                    <div class="stat-number" id="statRecetas">—</div>
                    <div class="stat-label">Recetas totales</div>
                </div>
            </div>
            <div class="stat-card-admin loading">
                <div class="stat-icon stat-icon-teal"><i class="fa-solid fa-layer-group"></i></div>
                <div class="stat-body">
                    <div class="stat-number" id="statCategorias">—</div>
                    <div class="stat-label">Categorías</div>
                </div>
            </div>
            <div class="stat-card-admin loading">
                <div class="stat-icon stat-icon-indigo"><i class="fa-solid fa-users"></i></div>
                <div class="stat-body">
                    <div class="stat-number" id="statUsuarios">—</div>
                    <div class="stat-label">Usuarios registrados</div>
                </div>
            </div>
            <div class="stat-card-admin loading">
                <div class="stat-icon stat-icon-rose"><i class="fa-solid fa-book"></i></div>
                <div class="stat-body">
                    <div class="stat-number" id="statLibros">—</div>
                    <div class="stat-label">Libros fuente</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <section class="quick-actions-section">
            <h2 class="section-title">Acciones Rápidas</h2>
            <div class="quick-actions-grid">
                <a href="recetas.php?action=new" class="quick-action-card">
                    <div class="qa-icon"><i class="fa-solid fa-plus"></i></div>
                    <div class="qa-text">Nueva Receta</div>
                    <div class="qa-desc">Añadir una receta al recetario</div>
                </a>
                <a href="recetas.php" class="quick-action-card">
                    <div class="qa-icon"><i class="fa-solid fa-list"></i></div>
                    <div class="qa-text">Gestionar Recetas</div>
                    <div class="qa-desc">Ver, editar y eliminar recetas</div>
                </a>
                <a href="usuarios.php?action=new" class="quick-action-card">
                    <div class="qa-icon"><i class="fa-solid fa-user-plus"></i></div>
                    <div class="qa-text">Nuevo Usuario</div>
                    <div class="qa-desc">Crear cuenta de acceso</div>
                </a>
                <a href="usuarios.php" class="quick-action-card">
                    <div class="qa-icon"><i class="fa-solid fa-users-gear"></i></div>
                    <div class="qa-text">Gestionar Usuarios</div>
                    <div class="qa-desc">Administrar cuentas y roles</div>
                </a>
            </div>
        </section>

        <!-- Categorías Overview -->
        <section class="section-card" id="categorySection" style="display:none">
            <h2 class="section-title">Distribución por Categoría</h2>
            <div class="category-bars" id="categoryBars"></div>
        </section>

    </main>
</div>

<script>
async function loadStats() {
    // Cargar recetas
    const rRes = await fetch('../api/recetas.php');
    const recetas = await rRes.json();

    // Cargar usuarios
    const uRes = await fetch('../api/usuarios.php');
    const usuarios = await uRes.json();

    // Estadísticas
    document.getElementById('statRecetas').textContent = recetas.length;
    document.getElementById('statUsuarios').textContent = usuarios.length;

    const cats = [...new Set(recetas.map(r => r.categoria))];
    document.getElementById('statCategorias').textContent = cats.length;

    const libros = [...new Set(recetas.map(r => r.libro).filter(Boolean))];
    document.getElementById('statLibros').textContent = libros.length;

    // Quitar loading
    document.querySelectorAll('.stat-card-admin').forEach(c => c.classList.remove('loading'));

    // Barras de categorías
    const catCount = {};
    recetas.forEach(r => { catCount[r.categoria] = (catCount[r.categoria] || 0) + 1; });
    const max = Math.max(...Object.values(catCount));

    const barsHtml = Object.entries(catCount)
        .sort((a,b) => b[1]-a[1])
        .map(([cat, count]) => `
            <div class="cat-bar-row">
                <span class="cat-bar-label">${cat}</span>
                <div class="cat-bar-track">
                    <div class="cat-bar-fill" style="width:${(count/max*100).toFixed(1)}%"></div>
                </div>
                <span class="cat-bar-count">${count}</span>
            </div>
        `).join('');

    document.getElementById('categoryBars').innerHTML = barsHtml;
    document.getElementById('categorySection').style.display = '';
}

loadStats().catch(console.error);
</script>
</body>
</html>
