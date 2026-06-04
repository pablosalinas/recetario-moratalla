# 🍲 Recetario Tradicional Moratallero

**Patrimonio Gastronómico de Moratalla** — Una web interactiva que preserva y digitaliza la cocina tradicional de Moratalla (Murcia, España).

---

## 🌐 Demo

Accede a la versión en vivo en: **[GitHub Pages](https://pablosalinas.github.io/recetario-moratalla/)**

---

## 📖 Sobre el Proyecto

Este recetario reúne más de **150 recetas tradicionales** digitalizadas a partir de dos publicaciones históricas:

| Libro | Año | Recetas |
|---|---|---|
| *Cocina Tradicional de Moratalla* | 1992 | 73 recetas |
| *Gastronomía de Moratalla* | 2001 | 82 recetas |

El proyecto surge de la necesidad de **preservar el patrimonio culinario** de Moratalla, un municipio de la Región de Murcia, digitalizando recetas que de otro modo correrían el riesgo de perderse con el paso del tiempo.

---

## ✨ Características

- 🔍 **Búsqueda reactiva** insensible a acentos y mayúsculas
- 🗂️ **Filtro por categoría** con carrusel interactivo
- 📋 **Panel de cocinado** con ingredientes tipo checklist y pasos numerados
- 💾 **Persistencia local** con `localStorage` (recuerda qué ingredientes has preparado)
- 🌙 **Diseño oscuro rústico** con paleta terracota/trigo premium
- 📱 **Totalmente responsive** para móvil, tablet y escritorio

---

## 🗂️ Estructura

```
recetario-moratalla/
├── index.html          # Estructura semántica HTML5
├── css/
│   └── style.css       # Diseño rustic dark-mode premium
├── js/
│   └── app.js          # Motor de búsqueda + panel interactivo
├── data/
│   └── recetas.json    # Base de datos completa de recetas
├── img/
│   └── ...             # Ilustraciones generadas con IA
└── README.md
```

---

## 🚀 Uso Local

Sirve cualquier carpeta con un servidor HTTP estático. Con Python:

```bash
python -m http.server 8000
```

O abre directamente `index.html` en tu navegador (la carga de JSON puede requerir un servidor local en algunos navegadores).

---

## 🙏 Créditos

- **Fuentes**: *Cocina Tradicional de Moratalla* (1992) y *Gastronomía de Moratalla* (2001)
- **Digitalización y desarrollo**: Pablo Salinas
- **Ilustraciones**: Generadas con IA (estilo rústico acuarela)

---

## 📜 Licencia

Contenido culinario de dominio cultural local. Código fuente bajo licencia **MIT**.
