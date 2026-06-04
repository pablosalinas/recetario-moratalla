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
    <title>Gestión de Recetas — Admin Recetario</title>
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
            <a href="index.php" class="nav-item"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="categorias.php" class="nav-item"><i class="fa-solid fa-layer-group"></i> Categorías</a>
            <a href="recetas.php" class="nav-item active"><i class="fa-solid fa-utensils"></i> Recetas</a>
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

    <!-- Main -->
    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Gestión de Recetas</h1>
                <p class="admin-page-sub">Crear, editar y eliminar recetas del recetario</p>
            </div>
            <button class="btn-primary" id="btnNuevaReceta">
                <i class="fa-solid fa-plus"></i> Nueva Receta
            </button>
        </header>

        <!-- Filtros -->
        <div class="filter-bar">
            <div class="search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchRecetas" placeholder="Buscar receta...">
            </div>
            <select id="filterCategoria" class="select-filter">
                <option value="">Todas las categorías</option>
            </select>
        </div>

        <!-- Tabla -->
        <div class="table-wrapper">
            <table class="admin-table" id="recetasTable">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Facilitador/a</th>
                        <th>Libro</th>
                        <th>Pág.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="recetasTbody">
                    <tr><td colspan="6" class="loading-row"><i class="fa-solid fa-spinner fa-spin"></i> Cargando recetas...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer" id="tableFooter"></div>
    </main>
</div>

<!-- Modal: Formulario Receta -->
<div class="modal-overlay hidden" id="modalReceta">
    <div class="modal-box modal-large">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Nueva Receta</h2>
            <button class="modal-close" id="btnCerrarModal" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="formReceta" novalidate>
                <input type="hidden" id="rId" name="id">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Nombre <span class="req">*</span></label>
                        <input type="text" id="rNombre" class="form-control" required placeholder="Nombre de la receta">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoría <span class="req">*</span></label>
                        <select id="rCategoria" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Facilitador/a</label>
                        <input type="text" id="rFacilitador" class="form-control" placeholder="Quién aportó la receta">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Libro Fuente</label>
                        <select id="rLibro" class="form-control">
                            <option value="Cocina Tradicional de Moratalla (1992)">Cocina Tradicional de Moratalla (1992)</option>
                            <option value="Gastronomía de Moratalla (2001)">Gastronomía de Moratalla (2001)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Página</label>
                        <input type="number" id="rPagina" class="form-control" min="1" placeholder="Nº de página">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Imagen Origen (nombre archivo)</label>
                        <input type="text" id="rImagenOrigen" class="form-control" placeholder="IMG_20250122_152636.jpg">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Imágenes (nombres de archivo o rutas)</label>
                        <div style="display: flex; gap: 0.5rem; align-items: stretch;">
                            <input type="text" id="rImagenes" class="form-control" placeholder="img/recetas/receta_123.jpg">
                            <input type="file" id="rImageUpload" accept="image/jpeg, image/png, image/webp" style="display: none;">
                            <button type="button" class="btn-secondary" id="btnUploadImage" style="white-space: nowrap;">
                                <i class="fa-solid fa-upload"></i> Subir
                            </button>
                        </div>
                        <span class="form-hint">Subir imagen optimizada con marca de agua (www.moratalla-murcia.com)</span>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end; padding-bottom: 0.5rem;">
                        <label class="toggle-label">
                            <input type="checkbox" id="rVisible" checked>
                            <span class="toggle-slider"></span>
                            <span>Receta Visible al público</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Ingredientes <span class="req">*</span></label>
                    <div id="ingredientesList" class="ingredients-editor"></div>
                    <button type="button" class="btn-add-ingredient" id="btnAddIngrediente">
                        <i class="fa-solid fa-plus"></i> Añadir ingrediente
                    </button>
                </div>

                <div class="form-group">
                    <label class="form-label">Instrucciones <span class="req">*</span></label>
                    <textarea id="rInstrucciones" class="form-control form-textarea" rows="6" placeholder="Descripción del proceso de elaboración..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Nota Histórica</label>
                    <textarea id="rNotaHistorica" class="form-control form-textarea" rows="3" placeholder="Contexto cultural o histórico de la receta..."></textarea>
                </div>

                <div class="form-error hidden" id="formError"></div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="btnCancelarForm">Cancelar</button>
                    <button type="submit" class="btn-primary" id="btnGuardarReceta">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Receta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Confirmar Borrado -->
<div class="modal-overlay hidden" id="modalConfirm">
    <div class="modal-box modal-small">
        <div class="modal-header">
            <h2 class="modal-title">¿Eliminar receta?</h2>
        </div>
        <div class="modal-body">
            <p class="confirm-text">Vas a eliminar <strong id="confirmRecetaNombre"></strong>. Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary" id="btnCancelarConfirm">Cancelar</button>
            <button class="btn-danger" id="btnConfirmarBorrar">
                <i class="fa-solid fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>

<script>
let allRecetas = [];
let deletingId = null;

const CATEGORIAS = {
    'Mojos y Entrantes': 'badge-amber',
    'Sopas y Caldos':    'badge-blue',
    'Arroces y Harinas': 'badge-gold',
    'Potajes y Guisos':  'badge-green',
    'Carnes':            'badge-rose',
    'Fritos y Pescados': 'badge-teal',
    'Repostería':        'badge-purple',
};

// ── Cargar recetas ─────────────────────────────────────────────────────────
async function loadRecetas() {
    const res = await fetch('../api/recetas.php');
    allRecetas = await res.json();
    renderTable(allRecetas);
}

function renderTable(recetas) {
    const tbody = document.getElementById('recetasTbody');
    document.getElementById('tableFooter').textContent = `${recetas.length} receta${recetas.length !== 1 ? 's' : ''}`;

    if (!recetas.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-row">No hay recetas que mostrar.</td></tr>';
        return;
    }

    tbody.innerHTML = recetas.map(r => `
        <tr data-id="${r.id}">
            <td class="td-name">
                ${escHtml(r.nombre)}
                ${r.visible === false ? '<span class="badge badge-gray" style="margin-left: 5px;">Oculta</span>' : ''}
            </td>
            <td><span class="badge ${CATEGORIAS[r.categoria] || 'badge-gray'}">${escHtml(r.categoria)}</span></td>
            <td>${escHtml(r.facilitador || '—')}</td>
            <td class="td-libro">${escHtml((r.libro || '').replace(' de Moratalla',''))}</td>
            <td>${r.pagina || '—'}</td>
            <td class="td-actions">
                <button class="btn-icon btn-edit" title="Editar" onclick="openEdit('${r.id}')">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn-icon btn-delete" title="Eliminar" onclick="openDelete('${r.id}', '${escHtml(r.nombre).replace(/'/g,"\\'")}')">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function escHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Filtros ────────────────────────────────────────────────────────────────
function applyFilters() {
    const q    = document.getElementById('searchRecetas').value.toLowerCase().normalize('NFD').replace(/\p{Mn}/gu,'');
    const cat  = document.getElementById('filterCategoria').value;
    const filtered = allRecetas.filter(r => {
        const matchQ   = !q || r.nombre.toLowerCase().normalize('NFD').replace(/\p{Mn}/gu,'').includes(q) ||
                         (r.facilitador||'').toLowerCase().includes(q);
        const matchCat = !cat || r.categoria === cat;
        return matchQ && matchCat;
    });
    renderTable(filtered);
}

document.getElementById('searchRecetas').addEventListener('input', applyFilters);
document.getElementById('filterCategoria').addEventListener('change', applyFilters);

// ── Modal Receta ───────────────────────────────────────────────────────────
function openModal(receta = null) {
    document.getElementById('formError').classList.add('hidden');
    document.getElementById('formError').textContent = '';

    if (receta) {
        document.getElementById('modalTitle').textContent  = 'Editar Receta';
        document.getElementById('rId').value              = receta.id;
        document.getElementById('rNombre').value          = receta.nombre;
        document.getElementById('rCategoria').value       = receta.categoria;
        document.getElementById('rFacilitador').value     = receta.facilitador || '';
        document.getElementById('rLibro').value           = receta.libro || 'Cocina Tradicional de Moratalla (1992)';
        document.getElementById('rPagina').value          = receta.pagina || '';
        document.getElementById('rImagenOrigen').value    = receta.imagen_origen || '';
        document.getElementById('rImagenes').value        = (receta.imagenes || []).join(', ');
        document.getElementById('rVisible').checked       = receta.visible !== false;
        document.getElementById('rInstrucciones').value   = receta.instrucciones || '';
        document.getElementById('rNotaHistorica').value   = receta.nota_historica || '';
        renderIngredientes(receta.ingredientes || []);
    } else {
        document.getElementById('modalTitle').textContent = 'Nueva Receta';
        document.getElementById('formReceta').reset();
        document.getElementById('rId').value = '';
        document.getElementById('rVisible').checked = true;
        renderIngredientes(['']);
    }

    document.getElementById('modalReceta').classList.remove('hidden');
    document.getElementById('rNombre').focus();
}

function closeModal() {
    document.getElementById('modalReceta').classList.add('hidden');
}

function openEdit(id) {
    const r = allRecetas.find(x => x.id === id);
    if (r) openModal(r);
}

// ── Editor de ingredientes ─────────────────────────────────────────────────
function renderIngredientes(list) {
    const cont = document.getElementById('ingredientesList');
    cont.innerHTML = list.map((ing, i) => ingredienteRow(ing, i)).join('');
}

function ingredienteRow(value, i) {
    return `
        <div class="ing-row" data-index="${i}">
            <input type="text" class="form-control ing-input" value="${escHtml(value)}" placeholder="Ingrediente ${i+1}">
            <button type="button" class="btn-icon btn-delete ing-remove" onclick="removeIngrediente(this)">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    `;
}

document.getElementById('btnAddIngrediente').addEventListener('click', () => {
    const cont  = document.getElementById('ingredientesList');
    const index = cont.children.length;
    cont.insertAdjacentHTML('beforeend', ingredienteRow('', index));
    cont.lastElementChild.querySelector('.ing-input').focus();
});

function removeIngrediente(btn) {
    const cont = document.getElementById('ingredientesList');
    if (cont.children.length > 1) {
        btn.closest('.ing-row').remove();
    }
}

function getIngredientes() {
    return [...document.querySelectorAll('.ing-input')]
        .map(i => i.value.trim())
        .filter(Boolean);
}

// ── Guardar receta ─────────────────────────────────────────────────────────
document.getElementById('formReceta').addEventListener('submit', async (e) => {
    e.preventDefault();

    const id     = document.getElementById('rId').value;
    const nombre = document.getElementById('rNombre').value.trim();
    const cat    = document.getElementById('rCategoria').value;

    if (!nombre || !cat) {
        showFormError('Nombre y categoría son obligatorios.');
        return;
    }

    const ings = getIngredientes();
    if (!ings.length) {
        showFormError('Añade al menos un ingrediente.');
        return;
    }

    const payload = {
        id:             id || undefined,
        nombre,
        categoria:      cat,
        facilitador:    document.getElementById('rFacilitador').value.trim(),
        libro:          document.getElementById('rLibro').value,
        pagina:         parseInt(document.getElementById('rPagina').value) || 0,
        imagen_origen:  document.getElementById('rImagenOrigen').value.trim(),
        imagenes:       document.getElementById('rImagenes').value.split(',').map(s => s.trim()).filter(Boolean),
        visible:        document.getElementById('rVisible').checked,
        ingredientes:   ings,
        instrucciones:  document.getElementById('rInstrucciones').value.trim(),
        nota_historica: document.getElementById('rNotaHistorica').value.trim(),
    };

    const method = id ? 'PUT' : 'POST';
    const btn    = document.getElementById('btnGuardarReceta');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    try {
        const res  = await fetch('../api/recetas.php', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (!res.ok) throw new Error(data.error || 'Error al guardar');

        closeModal();
        await loadRecetas();
        showToast(id ? 'Receta actualizada ✓' : 'Receta creada ✓');
    } catch (err) {
        showFormError(err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Receta';
    }
});

function showFormError(msg) {
    const el = document.getElementById('formError');
    el.textContent = msg;
    el.classList.remove('hidden');
}

// ── Eliminar receta ────────────────────────────────────────────────────────
function openDelete(id, nombre) {
    deletingId = id;
    document.getElementById('confirmRecetaNombre').textContent = nombre;
    document.getElementById('modalConfirm').classList.remove('hidden');
}

document.getElementById('btnConfirmarBorrar').addEventListener('click', async () => {
    if (!deletingId) return;
    const btn = document.getElementById('btnConfirmarBorrar');
    btn.disabled = true;

    try {
        const res  = await fetch(`../api/recetas.php?id=${encodeURIComponent(deletingId)}`, { method: 'DELETE' });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error);

        document.getElementById('modalConfirm').classList.add('hidden');
        await loadRecetas();
        showToast('Receta eliminada');
    } catch (err) {
        alert('Error: ' + err.message);
    } finally {
        btn.disabled = false;
        deletingId = null;
    }
});

// ── Toast notification ─────────────────────────────────────────────────────
function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 2500);
}

// ── Eventos de cierre ──────────────────────────────────────────────────────
document.getElementById('btnNuevaReceta').addEventListener('click', () => openModal(null));
document.getElementById('btnCerrarModal').addEventListener('click', closeModal);
document.getElementById('btnCancelarForm').addEventListener('click', closeModal);
document.getElementById('btnCancelarConfirm').addEventListener('click', () => {
    document.getElementById('modalConfirm').classList.add('hidden');
    deletingId = null;
});

// Cerrar modales al hacer click fuera
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.classList.add('hidden');
            deletingId = null;
        }
    });
});

// Atajos de teclado
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay:not(.hidden)').forEach(m => m.classList.add('hidden'));
    }
});

// Abrir directamente en "Nueva receta" si viene con ?action=new
if (new URLSearchParams(location.search).get('action') === 'new') {
    openModal(null);
}

// ── Subida de Imágenes ─────────────────────────────────────────────────────
document.getElementById('btnUploadImage').addEventListener('click', () => {
    document.getElementById('rImageUpload').click();
});

document.getElementById('rImageUpload').addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const btn = document.getElementById('btnUploadImage');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Subiendo...';

    const formData = new FormData();
    formData.append('imagen', file);

    try {
        const res = await fetch('../api/upload.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (!res.ok) throw new Error(data.error || 'Error al subir la imagen');
        
        // Añadir al campo de texto
        const input = document.getElementById('rImagenes');
        const currentVals = input.value.split(',').map(s => s.trim()).filter(Boolean);
        currentVals.push(data.filename);
        input.value = currentVals.join(', ');
        
        showToast('Imagen optimizada y subida correctamente ✓');
    } catch (err) {
        alert(err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
        e.target.value = ''; // Reset
    }
});

// Cargar categorías y luego recetas
async function init() {
    try {
        const catRes = await fetch('../api/categorias.php');
        const categorias = await catRes.json();
        
        const filterSelect = document.getElementById('filterCategoria');
        const formSelect = document.getElementById('rCategoria');
        
        categorias.forEach(c => {
            const opt1 = document.createElement('option');
            opt1.value = c.nombre; opt1.textContent = c.nombre;
            filterSelect.appendChild(opt1);
            
            const opt2 = document.createElement('option');
            opt2.value = c.nombre; opt2.textContent = c.nombre;
            formSelect.appendChild(opt2);
        });
    } catch (e) {
        console.error("Error cargando categorías", e);
    }
    
    await loadRecetas();
}

// Inicializar
init();
</script>
</body>
</html>
