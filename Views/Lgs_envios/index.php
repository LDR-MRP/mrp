<?php headerAdmin($data); ?>

<?php
$esModoPreEnvio = false;
$ocultarBreadcrumbsGrid = false;
include __DIR__ . '/_partial_envios_styles.php';
?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <?php include __DIR__ . '/_partial_envios_grid.php'; ?>
            <?php include __DIR__ . '/_partial_envios_form.php'; ?>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
