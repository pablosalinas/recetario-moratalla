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
    <title>Gestión de Categorías — Admin Recetario</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="fa-solid fa-mortar-pestle"></i></div>
            <div>
                <div class="brand-name">Recetario</div>
                <div class="brand-sub">Panel Admin</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="categorias.php" class="nav-item active"><i class="fa-solid fa-layer-group"></i> Categorías</a>
            <a href="recetas.php" class="nav-item"><i class="fa-solid fa-utensils"></i> Recetas</a>
            <a href="usuarios.php" class="nav-item"><i class="fa-solid fa-users"></i> Usuarios</a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-icon"><i class="fa-solid fa-user"></i></div>
                <div>
                    <div class="sidebar-user-name"><?= htmlspecialchars($user['nombre']) ?></div>
                    <div class="sidebar-user-role">Administrador</div>
                </div>
            </div>
            <a href="../index.php" class="btn-sidebar-action"><i class="fa-solid fa-arrow-left"></i> Ver Recetario</a>
            <a href="../logout.php" class="btn-sidebar-action btn-danger-soft"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Gestión de Categorías</h1>
                <p class="admin-page-sub">Crear, editar y organizar las categorías del recetario</p>
            </div>
            <button class="btn-primary" id="btnNuevaCat">
                <i class="fa-solid fa-plus"></i> Nueva Categoría
            </button>
        </header>

        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Imagen</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="catTbody">
                    <tr><td colspan="4" class="loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal: Formulario -->
<div class="modal-overlay hidden" id="modalCat">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Nueva Categoría</h2>
            <button class="modal-close" id="btnCerrarModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="formCat">
                <input type="hidden" id="cId">
                <div class="form-group">
                    <label class="form-label">Nombre <span class="req">*</span></label>
                    <input type="text" id="cNombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Imagen (ej. cat-carnes.png)</label>
                    <input type="text" id="cImagen" class="form-control">
                </div>
                <div class="form-group">
                    <label class="toggle-label">
                        <input type="checkbox" id="cVisible" checked>
                        <span class="toggle-slider"></span>
                        <span>Categoría Visible al público</span>
                    </label>
                </div>
                <div class="form-error hidden" id="formError"></div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="btnCancelar">Cancelar</button>
                    <button type="submit" class="btn-primary" id="btnGuardar">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let allData = [];

async function loadData() {
    const res = await fetch('../api/categorias.php');
    allData = await res.json();
    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('catTbody');
    if (!allData.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="empty-row">No hay categorías.</td></tr>';
        return;
    }
    tbody.innerHTML = allData.map(c => `
        <tr>
            <td><strong>${c.nombre}</strong></td>
            <td>${c.imagen || '—'}</td>
            <td>${c.visible ? '<span class="badge badge-green">Visible</span>' : '<span class="badge badge-gray">Oculta</span>'}</td>
            <td class="td-actions">
                <button class="btn-icon btn-edit" onclick="openEdit('${c.id}')"><i class="fa-solid fa-pen"></i></button>
                <button class="btn-icon btn-delete" onclick="deleteItem('${c.id}')"><i class="fa-solid fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function openModal(item = null) {
    document.getElementById('formError').classList.add('hidden');
    if (item) {
        document.getElementById('modalTitle').textContent = 'Editar Categoría';
        document.getElementById('cId').value = item.id;
        document.getElementById('cNombre').value = item.nombre;
        document.getElementById('cImagen').value = item.imagen || '';
        document.getElementById('cVisible').checked = item.visible !== false;
    } else {
        document.getElementById('modalTitle').textContent = 'Nueva Categoría';
        document.getElementById('formCat').reset();
        document.getElementById('cId').value = '';
        document.getElementById('cVisible').checked = true;
    }
    document.getElementById('modalCat').classList.remove('hidden');
}

function openEdit(id) {
    const it = allData.find(x => x.id === id);
    if (it) openModal(it);
}

document.getElementById('formCat').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('cId').value;
    const payload = {
        nombre: document.getElementById('cNombre').value,
        imagen: document.getElementById('cImagen').value,
        visible: document.getElementById('cVisible').checked
    };
    if (id) payload.id = id;

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    try {
        const res = await fetch('../api/categorias.php', {
            method: id ? 'PUT' : 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        if (!res.ok) throw new Error('Error al guardar');
        document.getElementById('modalCat').classList.add('hidden');
        loadData();
    } catch (e) {
        document.getElementById('formError').textContent = e.message;
        document.getElementById('formError').classList.remove('hidden');
    } finally {
        btn.disabled = false;
    }
});

async function deleteItem(id) {
    if (!confirm('¿Eliminar categoría?')) return;
    await fetch('../api/categorias.php?id=' + id, {method: 'DELETE'});
    loadData();
}

document.getElementById('btnNuevaCat').addEventListener('click', () => openModal());
document.getElementById('btnCerrarModal').addEventListener('click', () => document.getElementById('modalCat').classList.add('hidden'));
document.getElementById('btnCancelar').addEventListener('click', () => document.getElementById('modalCat').classList.add('hidden'));

loadData();
</script>
</body>
</html>
