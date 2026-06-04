/* -------------------------------------------------------------
 * RECETARIO TRADICIONAL DE MORATALLA
 * Lógica de la Aplicación (Filtros, Búsqueda, Detalles y Progreso)
 * ------------------------------------------------------------- */

document.addEventListener("DOMContentLoaded", () => {
    // Estado de la Aplicación
    let recipes = [];
    let activeCategory = "all";
    let searchQuery = "";
    
    // Elementos del DOM
    const recipesGrid = document.getElementById("recipes-grid-container");
    const searchInput = document.getElementById("recipe-search-input");
    const clearSearchBtn = document.getElementById("clear-search-btn");
    const categoryCards = document.querySelectorAll(".category-card");
    const counterText = document.getElementById("recipes-counter-text");
    const noRecipesElement = document.getElementById("no-recipes-element");
    
    // Elementos del Drawer (Detalles)
    const detailDrawer = document.getElementById("recipe-detail-drawer");
    const drawerOverlay = document.getElementById("drawer-close-overlay");
    const closeDrawerBtn = document.getElementById("close-drawer-btn");
    const drawerInnerContent = document.getElementById("drawer-inner-content");

    // 1. Cargar datos del JSON
    async function loadRecipes() {
        try {
            const response = await fetch("data/recetas.json");
            if (!response.ok) {
                throw new Error("No se pudo cargar la base de datos de recetas");
            }
            recipes = await response.json();
            renderRecipes();
        } catch (error) {
            console.error("Error al inicializar el recetario:", error);
            counterText.textContent = "Error al cargar las recetas tradicionales.";
        }
    }

    // Normalizador de acentos y mayúsculas
    function normalizeString(str) {
        return str
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    // 2. Filtrar recetas según categoría y búsqueda
    function getFilteredRecipes() {
        return recipes.filter(recipe => {
            // Filtro de Categoría
            const matchesCategory = activeCategory === "all" || recipe.categoria === activeCategory;
            
            // Filtro de Búsqueda
            const normalizedSearch = normalizeString(searchQuery);
            const matchesSearch = !normalizedSearch || 
                normalizeString(recipe.nombre).includes(normalizedSearch) ||
                normalizeString(recipe.facilitador).includes(normalizedSearch) ||
                normalizeString(recipe.categoria).includes(normalizedSearch) ||
                (recipe.libro && normalizeString(recipe.libro).includes(normalizedSearch)) ||
                recipe.ingredientes.some(ing => normalizeString(ing).includes(normalizedSearch)) ||
                normalizeString(recipe.instrucciones).includes(normalizedSearch);
                
            return matchesCategory && matchesSearch;
        });
    }

    // 3. Renderizar las tarjetas en la cuadrícula
    function renderRecipes() {
        const filtered = getFilteredRecipes();
        recipesGrid.innerHTML = "";
        
        // Actualizar contador
        if (filtered.length === 1) {
            counterText.textContent = "Se muestra 1 receta tradicional";
        } else if (filtered.length > 0) {
            counterText.textContent = `Se muestran ${filtered.length} recetas tradicionales`;
        } else {
            counterText.textContent = "No se encontraron recetas";
        }

        // Mostrar / Ocultar estado vacío
        if (filtered.length === 0) {
            noRecipesElement.classList.remove("hidden");
            return;
        } else {
            noRecipesElement.classList.add("hidden");
        }

        // Crear tarjetas
        filtered.forEach(recipe => {
            const card = document.createElement("article");
            card.className = "recipe-card-item";
            card.setAttribute("role", "button");
            card.setAttribute("tabindex", "0");
            card.dataset.id = recipe.id;

            // Heurística de imagen: Si es Mojos, poner Mojos, etc.
            const headerImage = getCategoryImage(recipe.categoria);

            card.innerHTML = `
                <div class="recipe-card-header" style="background-image: url('${headerImage}');">
                    <span class="recipe-card-cat-badge">${recipe.categoria}</span>
                    <span class="recipe-card-page-badge">Pág. ${recipe.pagina}</span>
                    <h3 class="recipe-card-title">${recipe.nombre}</h3>
                </div>
                <div class="recipe-card-body">
                    <div class="recipe-card-meta">
                        <i class="fa-solid fa-mortar-pestle"></i>
                        <span>Facilitada por <strong>${recipe.facilitador}</strong></span>
                    </div>
                    <div class="recipe-card-meta" style="margin-top: 4px; font-size: 0.85em; opacity: 0.8;">
                        <i class="fa-solid fa-book"></i>
                        <span>Libro: <strong>${recipe.libro || 'Cocina Tradicional de Moratalla (1992)'}</strong></span>
                    </div>
                    <p class="recipe-card-excerpt">${recipe.instrucciones}</p>
                    <div class="recipe-card-footer">
                        <span class="ingredients-preview-badge">
                            <strong>${recipe.ingredientes.length}</strong> ingredientes
                        </span>
                        <span class="recipe-card-btn">
                            Cocinar <i class="fa-solid fa-arrow-right-long"></i>
                        </span>
                    </div>
                </div>
            `;

            // Eventos para abrir el detalle
            card.addEventListener("click", () => openRecipeDetail(recipe));
            card.addEventListener("keydown", (e) => {
                if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    openRecipeDetail(recipe);
                }
            });

            recipesGrid.appendChild(card);
        });
    }

    // Retorna la imagen adecuada para la tarjeta según la categoría
    function getCategoryImage(categoria) {
        switch (categoria) {
            case "Mojos y Entrantes": return "img/cat-entradas.png";
            case "Sopas y Caldos": return "img/cat-sopas.png";
            case "Arroces y Harinas": return "img/cat-arroces.png";
            case "Potajes y Guisos": return "img/cat-potajes.png";
            case "Carnes": return "img/cat-carnes.png";
            case "Fritos y Pescados": return "img/cat-pescados.png";
            case "Repostería": return "img/cat-reposteria.png";
            default: return "img/hero-bg.png";
        }
    }

    // 4. Detalle y panel lateral (Drawer)
    function openRecipeDetail(recipe) {
        // Generar contenido del Drawer
        
        // Limpieza y partición de instrucciones en oraciones/pasos
        // Se dividen por el punto seguido, evitando oraciones vacías
        const rawSteps = recipe.instrucciones
            .split(/(?<=[.!?])\s+/)
            .map(step => step.trim())
            .filter(step => step.length > 3);

        // Claves para el progreso local
        const progressKeyIng = `recipe_${recipe.id}_ingredients`;
        const progressKeySteps = `recipe_${recipe.id}_steps`;
        
        // Leer estado guardado
        const savedIngs = JSON.parse(localStorage.getItem(progressKeyIng)) || {};
        const savedSteps = JSON.parse(localStorage.getItem(progressKeySteps)) || {};

        let ingredientsHTML = "";
        recipe.ingredientes.forEach((ing, index) => {
            const isChecked = savedIngs[index] ? "checked" : "";
            ingredientsHTML += `
                <li>
                    <label class="ingredient-check-item">
                        <input type="checkbox" class="ingredient-checkbox-hidden" data-index="${index}" ${isChecked}>
                        <span class="ingredient-custom-checkbox">
                            <i class="fa-solid fa-check"></i>
                        </span>
                        <span class="ingredient-text">${ing}</span>
                    </label>
                </li>
            `;
        });

        let stepsHTML = "";
        rawSteps.forEach((step, index) => {
            const isChecked = savedSteps[index] ? "checked" : "";
            stepsHTML += `
                <div class="step-card-wrapper">
                    <input type="checkbox" id="step-chk-${index}" class="step-check-hidden hidden" data-index="${index}" ${isChecked}>
                    <label for="step-chk-${index}" class="instruction-step-card">
                        <div class="step-number">${index + 1}</div>
                        <div class="step-content">${step}</div>
                    </label>
                </div>
            `;
        });

        // Apunte / Nota Histórica
        let noteHTML = "";
        if (recipe.nota_historica) {
            noteHTML = `
                <div class="detail-historical-note">
                    <h4 class="historical-title">
                        <i class="fa-solid fa-circle-info"></i> Sabiduría Popular
                    </h4>
                    <p class="historical-text">"${recipe.nota_historica}"</p>
                </div>
            `;
        }

        drawerInnerContent.innerHTML = `
            <span class="detail-category-badge">${recipe.categoria}</span>
            <h2 class="detail-title">${recipe.nombre}</h2>
            
            <div class="detail-meta-box">
                <div class="meta-info-item">
                    <i class="fa-solid fa-mortar-pestle"></i>
                    <span>Facilitado por: <strong>${recipe.facilitador}</strong></span>
                </div>
                <div class="meta-info-item">
                    <i class="fa-solid fa-book-open"></i>
                    <span>Libro: <strong>${recipe.libro || 'Cocina Tradicional de Moratalla (1992)'}</strong> | Pág. <strong>${recipe.pagina}</strong></span>
                </div>
                <div class="meta-info-item">
                    <i class="fa-solid fa-image"></i>
                    <span>Origen: <strong>${recipe.imagen_origen}</strong></span>
                </div>
            </div>

            <div class="detail-ingredients-section">
                <h3 class="detail-section-title">Ingredientes Necesarios</h3>
                <div class="detail-ingredients-card">
                    <ul class="ingredients-checklist">
                        ${ingredientsHTML}
                    </ul>
                </div>
            </div>

            <div class="detail-instructions-section">
                <h3 class="detail-section-title">Preparación Paso a Paso</h3>
                <div class="interactive-steps-container">
                    ${stepsHTML}
                </div>
            </div>

            ${noteHTML}
        `;

        // Añadir escuchas de cambio para persistir el progreso
        const ingCheckboxes = drawerInnerContent.querySelectorAll(".ingredient-checkbox-hidden");
        ingCheckboxes.forEach(chk => {
            chk.addEventListener("change", (e) => {
                const idx = e.target.dataset.index;
                const state = JSON.parse(localStorage.getItem(progressKeyIng)) || {};
                if (e.target.checked) {
                    state[idx] = true;
                } else {
                    delete state[idx];
                }
                localStorage.setItem(progressKeyIng, JSON.stringify(state));
            });
        });

        const stepCheckboxes = drawerInnerContent.querySelectorAll(".step-check-hidden");
        stepCheckboxes.forEach(chk => {
            chk.addEventListener("change", (e) => {
                const idx = e.target.dataset.index;
                const state = JSON.parse(localStorage.getItem(progressKeySteps)) || {};
                if (e.target.checked) {
                    state[idx] = true;
                } else {
                    delete state[idx];
                }
                localStorage.setItem(progressKeySteps, JSON.stringify(state));
            });
        });

        // Abrir Drawer con animación
        detailDrawer.classList.add("open");
        detailDrawer.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden"; // Deshabilitar scroll de fondo
    }

    function closeRecipeDetail() {
        detailDrawer.classList.remove("open");
        detailDrawer.setAttribute("aria-hidden", "true");
        document.body.style.overflow = ""; // Rehabilitar scroll de fondo
    }

    // Eventos del Drawer
    closeDrawerBtn.addEventListener("click", closeRecipeDetail);
    drawerOverlay.addEventListener("click", closeRecipeDetail);
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && detailDrawer.classList.contains("open")) {
            closeRecipeDetail();
        }
    });

    // 5. Manejo del Buscador
    searchInput.addEventListener("input", (e) => {
        searchQuery = e.target.value;
        if (searchQuery.trim() !== "") {
            clearSearchBtn.style.display = "block";
        } else {
            clearSearchBtn.style.display = "none";
        }
        renderRecipes();
    });

    clearSearchBtn.addEventListener("click", () => {
        searchInput.value = "";
        searchQuery = "";
        clearSearchBtn.style.display = "none";
        renderRecipes();
        searchInput.focus();
    });

    // 6. Manejo de las Pestañas de Categoría
    categoryCards.forEach(card => {
        card.addEventListener("click", () => {
            // Remover clase activa de anteriores
            categoryCards.forEach(c => {
                c.classList.remove("active");
                c.setAttribute("aria-selected", "false");
            });

            // Establecer activa la actual
            card.classList.add("active");
            card.setAttribute("aria-selected", "true");
            activeCategory = card.dataset.category;

            renderRecipes();
            
            // Hacer scroll suave al inicio de las recetas si estamos abajo
            const rect = recipesGrid.getBoundingClientRect();
            if (rect.top < 0) {
                document.getElementById("recetario-section").scrollIntoView({ behavior: "smooth" });
            }
        });
    });

    // Cargar recetario al iniciar
    loadRecipes();
});
