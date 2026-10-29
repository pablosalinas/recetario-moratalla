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
    <title>Gestión de Usuarios — Admin Recetario</title>
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
            <a href="categorias.php" class="nav-item"><i class="fa-solid fa-layer-group"></i> Categorías</a>
            <a href="recetas.php" class="nav-item"><i class="fa-solid fa-utensils"></i> Recetas</a>
            <a href="usuarios.php" class="nav-item active"><i class="fa-solid fa-users"></i> Usuarios</a>
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
                <h1 class="admin-page-title">Gestión de Usuarios</h1>
                <p class="admin-page-sub">Administrar cuentas y roles de acceso</p>
            </div>
            <button class="btn-primary" id="btnNuevoUsuario">
                <i class="fa-solid fa-user-plus"></i> Nuevo Usuario
            </button>
        </header>

        <div class="filter-bar">
            <div class="search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchUsuarios" placeholder="Buscar por nombre o usuario...">
            </div>
        </div>

        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="usuariosTbody">
                    <tr><td colspan="6" class="loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="table-footer" id="tableFooter"></div>
    </main>
</div>

<!-- Modal: Formulario Usuario -->
<div class="modal-overlay hidden" id="modalUsuario">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modalUserTitle">Nuevo Usuario</h2>
            <button class="modal-close" id="btnCerrarUserModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="formUsuario" novalidate>
                <input type="hidden" id="uId">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Username <span class="req">*</span></label>
                        <input type="text" id="uUsername" class="form-control" required autocomplete="off" placeholder="nombre_usuario">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre Completo <span class="req">*</span></label>
                        <input type="text" id="uNombre" class="form-control" required placeholder="Nombre y apellidos">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña <span id="passReq" class="req">*</span></label>
                        <div class="input-pass-wrapper">
                            <input type="password" id="uPassword" class="form-control" autocomplete="new-password" placeholder="Mínimo 6 caracteres">
                            <button type="button" class="toggle-pass-btn" id="toggleUserPass">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-hint" id="passHint"></small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rol <span class="req">*</span></label>
                        <select id="uRol" class="form-control" required>
                            <option value="consulta">Consulta (solo lectura)</option>
                            <option value="admin">Administrador (acceso total)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="activoGroup" style="display:none">
                    <label class="toggle-label">
                        <input type="checkbox" id="uActivo" checked>
                        <span class="toggle-slider"></span>
                        <span>Cuenta activa</span>
                    </label>
                </div>

                <div class="form-error hidden" id="userFormError"></div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="btnCancelarUser">Cancelar</button>
                    <button type="submit" class="btn-primary" id="btnGuardarUsuario">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Confirmar borrado -->
<div class="modal-overlay hidden" id="modalConfirmUser">
    <div class="modal-box modal-small">
        <div class="modal-header">
            <h2 class="modal-title">¿Eliminar usuario?</h2>
        </div>
        <div class="modal-body">
            <p class="confirm-text">Vas a eliminar a <strong id="confirmUserNombre"></strong>. Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary" id="btnCancelarConfirmUser">Cancelar</button>
            <button class="btn-danger" id="btnConfirmarBorrarUser">
                <i class="fa-solid fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>

<script>
const ME_ID = <?= json_encode($user['id']) ?>;
let allUsers  = [];
let deletingUserId = null;

// ── Cargar usuarios ────────────────────────────────────────────────────────
async function loadUsuarios() {
    const res = await fetch('../api/usuarios.php');
    allUsers  = await res.json();
    renderTable(allUsers);
}

function renderTable(users) {
    const tbody = document.getElementById('usuariosTbody');
    document.getElementById('tableFooter').textContent = `${users.length} usuario${users.length !== 1 ? 's' : ''}`;

    if (!users.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-row">No hay usuarios.</td></tr>';
        return;
    }

    tbody.innerHTML = users.map(u => {
        const isMe  = u.id === ME_ID;
        const rolBadge = u.rol === 'admin'
            ? '<span class="badge badge-gold">⚙ Admin</span>'
            : '<span class="badge badge-blue">Consulta</span>';
        const actBadge = u.activo
            ? '<span class="badge badge-green">Activo</span>'
            : '<span class="badge badge-gray" title="Pendiente de autorización">Inactivo</span>';
        const meTag = isMe ? ' <span class="badge badge-amber" title="Tu cuenta">Tú</span>' : '';

        return `
            <tr>
                <td><strong>${escHtml(u.username)}</strong>${meTag}</td>
                <td>${escHtml(u.nombre)}</td>
                <td>${rolBadge}</td>
                <td>${actBadge}</td>
                <td>${u.created_at || '—'}</td>
                <td class="td-actions">
                    ${!u.activo ? `
                    <button class="btn-icon" style="color:#34d399; border-color:rgba(52,211,153,0.35);" onclick="authorizeUser(${u.id})" title="Autorizar y activar usuario">
                        <i class="fa-solid fa-user-check"></i>
                    </button>` : ''}
                    <button class="btn-icon btn-edit" onclick="openEditUser(${u.id})" title="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    ${!isMe ? `
                    <button class="btn-icon btn-delete" onclick="openDeleteUser(${u.id}, '${escHtml(u.nombre).replace(/'/g, "\\'")}')">
                        <i class="fa-solid fa-trash"></i>
                    </button>` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

async function authorizeUser(id) {
    const u = allUsers.find(x => x.id === id);
    if (!u) return;
    try {
        const res = await fetch('../api/usuarios.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: u.id, nombre: u.nombre, rol: u.rol, activo: true }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Error al autorizar');
        await loadUsuarios();
        showToast(`Usuario «${u.username}» autorizado y activado ✓`);
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

function escHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Filtro búsqueda ────────────────────────────────────────────────────────
document.getElementById('searchUsuarios').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    const filtered = allUsers.filter(u =>
        u.username.toLowerCase().includes(q) || u.nombre.toLowerCase().includes(q)
    );
    renderTable(filtered);
});

// ── Modal Usuario ──────────────────────────────────────────────────────────
function openNewUser() {
    document.getElementById('modalUserTitle').textContent = 'Nuevo Usuario';
    document.getElementById('formUsuario').reset();
    document.getElementById('uId').value = '';
    document.getElementById('passReq').style.display = '';
    document.getElementById('passHint').textContent = '';
    document.getElementById('activoGroup').style.display = 'none';
    document.getElementById('userFormError').classList.add('hidden');
    document.getElementById('modalUsuario').classList.remove('hidden');
    document.getElementById('uUsername').focus();
}

function openEditUser(id) {
    const u = allUsers.find(x => x.id === id);
    if (!u) return;

    document.getElementById('modalUserTitle').textContent = 'Editar Usuario';
    document.getElementById('uId').value       = u.id;
    document.getElementById('uUsername').value = u.username;
    document.getElementById('uNombre').value   = u.nombre;
    document.getElementById('uPassword').value = '';
    document.getElementById('uRol').value      = u.rol;
    document.getElementById('uActivo').checked = u.activo;

    // En edición, contraseña es opcional
    document.getElementById('passReq').style.display = 'none';
    document.getElementById('passHint').textContent   = 'Dejar en blanco para no cambiarla';
    document.getElementById('activoGroup').style.display = '';
    document.getElementById('userFormError').classList.add('hidden');

    // No se puede cambiar el rol propio
    document.getElementById('uRol').disabled = (u.id === ME_ID);

    document.getElementById('modalUsuario').classList.remove('hidden');
    document.getElementById('uNombre').focus();
}

function closeUserModal() {
    document.getElementById('modalUsuario').classList.add('hidden');
    document.getElementById('uRol').disabled = false;
}

// Toggle contraseña
document.getElementById('toggleUserPass').addEventListener('click', function() {
    const inp = document.getElementById('uPassword');
    const isPass = inp.type === 'password';
    inp.type = isPass ? 'text' : 'password';
    this.querySelector('i').className = isPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});

// ── Guardar usuario ────────────────────────────────────────────────────────
document.getElementById('formUsuario').addEventListener('submit', async (e) => {
    e.preventDefault();

    const id       = document.getElementById('uId').value;
    const username = document.getElementById('uUsername').value.trim();
    const nombre   = document.getElementById('uNombre').value.trim();
    const password = document.getElementById('uPassword').value;
    const rol      = document.getElementById('uRol').value;
    const activo   = document.getElementById('uActivo').checked;

    if (!username || !nombre) {
        showUserError('Usuario y nombre son obligatorios.');
        return;
    }
    if (!id && !password) {
        showUserError('La contraseña es obligatoria para nuevos usuarios.');
        return;
    }
    if (password && password.length < 6) {
        showUserError('La contraseña debe tener al menos 6 caracteres.');
        return;
    }

    const payload = { username, nombre, rol, activo };
    if (id) payload.id = parseInt(id);
    if (password) payload.password = password;

    const method = id ? 'PUT' : 'POST';
    const btn    = document.getElementById('btnGuardarUsuario');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    try {
        const res  = await fetch('../api/usuarios.php', {
            method,
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Error al guardar');

        closeUserModal();
        await loadUsuarios();
        showToast(id ? 'Usuario actualizado ✓' : 'Usuario creado ✓');
    } catch (err) {
        showUserError(err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Usuario';
    }
});

function showUserError(msg) {
    const el = document.getElementById('userFormError');
    el.textContent = msg;
    el.classList.remove('hidden');
}

// ── Eliminar usuario ───────────────────────────────────────────────────────
function openDeleteUser(id, nombre) {
    deletingUserId = id;
    document.getElementById('confirmUserNombre').textContent = nombre;
    document.getElementById('modalConfirmUser').classList.remove('hidden');
}

document.getElementById('btnConfirmarBorrarUser').addEventListener('click', async () => {
    if (!deletingUserId) return;
    const btn = document.getElementById('btnConfirmarBorrarUser');
    btn.disabled = true;

    try {
        const res  = await fetch(`../api/usuarios.php?id=${deletingUserId}`, { method: 'DELETE' });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error);

        document.getElementById('modalConfirmUser').classList.add('hidden');
        await loadUsuarios();
        showToast('Usuario eliminado');
    } catch (err) {
        alert('Error: ' + err.message);
    } finally {
        btn.disabled = false;
        deletingUserId = null;
    }
});

function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 2500);
}

// ── Eventos ────────────────────────────────────────────────────────────────
document.getElementById('btnNuevoUsuario').addEventListener('click', openNewUser);
document.getElementById('btnCerrarUserModal').addEventListener('click', closeUserModal);
document.getElementById('btnCancelarUser').addEventListener('click', closeUserModal);
document.getElementById('btnCancelarConfirmUser').addEventListener('click', () => {
    document.getElementById('modalConfirmUser').classList.add('hidden');
    deletingUserId = null;
});

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.add('hidden'); });
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay:not(.hidden)').forEach(m => m.classList.add('hidden'));
});

if (new URLSearchParams(location.search).get('action') === 'new') openNewUser();

loadUsuarios();
</script>
</body>
</html>
