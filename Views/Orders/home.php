<?php
headerOrders($data);
?>

<main>

    <!-- =====================================================
         HERO
    ====================================================== -->
    <section class="hero">

        <div class="container hero-grid">

            <div class="hero-text">

                <span class="tag">
                    Portal B2B automotriz
                </span>

                <h1>
                    Solicita unidades para tu distribuidora
                    de forma rápida y profesional.
                </h1>

                <p>
                    Consulta el catálogo disponible, compara modelos,
                    agrega las unidades que necesitas y genera una
                    solicitud con trazabilidad completa.
                </p>

                <div class="hero-actions">

                    <a
                        href="#catalogo"
                        class="btn btn-primary btn-large">

                        Ver catálogo
                    </a>

                    <a
                        href="<?= base_url(); ?>/orders/carrito"
                        class="btn btn-outline btn-large">

                        Ver carrito
                    </a>

                </div>

            </div>

            <div class="hero-card">

                <img
                    src="<?= media(); ?>/images/order_01.jpg"
                    alt="Unidades automotrices">

                <div class="hero-card-info">

                    <strong>
                        Pedidos centralizados
                    </strong>

                    <span>
                        Consulta modelos, cantidades y solicitudes
                        desde un solo lugar.
                    </span>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         CATÁLOGO
    ====================================================== -->

    <section
        class="catalog catalog-distribuidores"
        id="catalogo">

        <div class="container">

            <!-- TÍTULO -->

            <div class="section-title">

                <span>
                    Catálogo
                </span>

                <h2>
                    Unidades disponibles para distribuidores
                </h2>

                <p class="section-subtitle">
                    Consulta modelos disponibles y utiliza los filtros
                    para encontrar rápidamente la unidad que necesitas.
                </p>

            </div>


            <!-- CABECERA DEL CATÁLOGO -->

            <div class="catalog-toolbar">

                <div>

                    <strong id="catalogResults">
                        0 unidades encontradas
                    </strong>

                    <span id="catalogPageInfo">
                        Mostrando catálogo disponible
                    </span>

                </div>

                <div class="catalog-sort">

                    <label for="sortProducts">
                        Ordenar por:
                    </label>

                    <select id="sortProducts">

                        <option value="nombre">
                            Nombre
                        </option>

                        <option value="precio_asc">
                            Precio: menor a mayor
                        </option>

                        <option value="precio_desc">
                            Precio: mayor a menor
                        </option>

                        <option value="anio_desc">
                            Año: más reciente
                        </option>

                        <option value="stock_desc">
                            Mayor disponibilidad
                        </option>

                    </select>

                </div>

            </div>


            <!-- LAYOUT CATÁLOGO -->

            <div class="catalog-layout">

                <!-- =================================================
                     FILTROS
                ================================================== -->

                <aside class="catalog-filters">

                    <div class="filters-header">

                        <div>

                            <span>
                                Búsqueda
                            </span>

                            <h3>
                                Filtrar unidades
                            </h3>

                        </div>

                        <button
                            type="button"
                            class="filters-clear"
                            id="btnClearFilters">

                            Limpiar
                        </button>

                    </div>


                    <!-- BUSCADOR -->

                    <div class="filter-group">

                        <label for="searchInput">
                            Buscar unidad
                        </label>

                        <div class="filter-search">

                            <span>
                                🔍
                            </span>

                            <input
                                type="text"
                                id="searchInput"
                                placeholder="Modelo, nombre, versión...">

                        </div>

                    </div>


                    <!-- MARCA -->

                    <div class="filter-group">

                        <label for="brandFilter">
                            Marca
                        </label>

                        <select id="brandFilter">

                            <option value="todos">
                                Todas las marcas
                            </option>

                        </select>

                    </div>


                    <!-- MODELO -->

                    <div class="filter-group">

                        <label for="modelFilter">
                            Modelo
                        </label>

                        <select id="modelFilter">

                            <option value="todos">
                                Todos los modelos
                            </option>

                        </select>

                    </div>


                    <!-- AÑO -->

                    <div class="filter-group">

                        <label>
                            Año
                        </label>

                        <div class="filter-grid-two">

                            <select id="yearFromFilter">

                                <option value="">
                                    Desde
                                </option>

                            </select>

                            <select id="yearToFilter">

                                <option value="">
                                    Hasta
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- PRECIO -->

                    <div class="filter-group">

                        <label>
                            Rango de precio
                        </label>

                        <div class="filter-grid-two">

                            <div class="price-filter-input">

                                <span>$</span>

                                <input
                                    type="number"
                                    id="priceFromFilter"
                                    min="0"
                                    step="1000"
                                    placeholder="Desde">

                            </div>

                            <div class="price-filter-input">

                                <span>$</span>

                                <input
                                    type="number"
                                    id="priceToFilter"
                                    min="0"
                                    step="1000"
                                    placeholder="Hasta">

                            </div>

                        </div>

                    </div>


                    <!-- DISPONIBILIDAD -->

                    <div class="filter-group">

                        <label for="stockFilter">
                            Disponibilidad
                        </label>

                        <select id="stockFilter">

                            <option value="todos">
                                Todas
                            </option>

                            <option value="disponible">
                                Con disponibilidad
                            </option>

                            <option value="sin_stock">
                                Sin disponibilidad
                            </option>

                        </select>

                    </div>


                    <!-- BOTÓN -->

                    <button
                        type="button"
                        class="btn btn-primary btn-full"
                        id="btnApplyFilters">

                        Aplicar filtros
                    </button>

                </aside>


                <!-- =================================================
                     RESULTADOS
                ================================================== -->

                <div class="catalog-results">

                    <div
                        class="products-grid products-grid-catalog"
                        id="productsGrid">
                    </div>


                    <!-- PAGINADOR -->

                    <div
                        class="catalog-pagination"
                        id="catalogPagination">
                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         BENEFICIOS
    ====================================================== -->

    <section
        class="benefits"
        id="beneficios">

        <div class="container">

            <div class="section-title">

                <span>
                    Beneficios
                </span>

                <h2>
                    Beneficios del portal para distribuidores
                </h2>

                <p class="section-subtitle">
                    Una plataforma especializada para gestionar
                    solicitudes de unidades automotrices.
                </p>

            </div>


            <div class="benefits-grid benefits-grid-extended">

                <div class="benefit-card">

                    <span>01</span>

                    <h3>
                        Solicitudes centralizadas
                    </h3>

                    <p>
                        Genera solicitudes con modelos, versiones,
                        cantidades y requerimientos comerciales.
                    </p>

                </div>

                <div class="benefit-card">

                    <span>02</span>

                    <h3>
                        Catálogo actualizado
                    </h3>

                    <p>
                        Consulta información actualizada de unidades
                        disponibles para distribuidores.
                    </p>

                </div>

                <div class="benefit-card">

                    <span>03</span>

                    <h3>
                        Detalle de unidad
                    </h3>

                    <p>
                        Consulta especificaciones, imágenes, versiones
                        y características antes de solicitar.
                    </p>

                </div>

                <div class="benefit-card">

                    <span>04</span>

                    <h3>
                        Carrito B2B
                    </h3>

                    <p>
                        Consolida distintos modelos y cantidades dentro
                        de una sola solicitud.
                    </p>

                </div>

                <div class="benefit-card">

                    <span>05</span>

                    <h3>
                        Control comercial
                    </h3>

                    <p>
                        Administración podrá analizar demanda,
                        disponibilidad y prioridades.
                    </p>

                </div>

                <div class="benefit-card">

                    <span>06</span>

                    <h3>
                        Trazabilidad
                    </h3>

                    <p>
                        Consulta posteriormente el avance de cada
                        solicitud hasta facturación.
                    </p>

                </div>

            </div>


            <div class="process-panel">

                <div>

                    <span class="tag">
                        Flujo del pedido
                    </span>

                    <h3>
                        Del catálogo a la solicitud
                    </h3>

                    <p>
                        Consulta unidades, revisa sus características,
                        agrega cantidades y genera tu solicitud.
                    </p>

                </div>

                <div class="process-steps">

                    <div>
                        <strong>1</strong>
                        <span>Buscar</span>
                    </div>

                    <div>
                        <strong>2</strong>
                        <span>Ver detalle</span>
                    </div>

                    <div>
                        <strong>3</strong>
                        <span>Agregar</span>
                    </div>

                    <div>
                        <strong>4</strong>
                        <span>Solicitar</span>
                    </div>

                </div>

            </div>

        </div>

    </section>

</main>

<?php
footerOrders($data);
?>