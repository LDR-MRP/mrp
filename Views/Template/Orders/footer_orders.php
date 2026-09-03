<footer class="footer">
  <div class="container">
    <p>© <?= date('Y'); ?> LDR Solutions. Portal de pedidos para distribuidores.</p>
  </div>
</footer>

<script>
  const base_url = "<?= base_url(); ?>";
  const media_url = "<?= media(); ?>";
</script>

<script src="<?= media(); ?>/js/orders/home.js"></script>
   <script src="<?= media(); ?>/minimal/libs/sweetalert2/sweetalert2.min.js"></script>

   <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/vfs_fonts.js"></script>
<?php
if (!empty($data['page_functions_js']) && is_array($data['page_functions_js'])) {
  foreach ($data['page_functions_js'] as $js) {
    echo '<script src="' . media() . '/js/' . $js . '?v=1.0.0.4"></script>' . PHP_EOL;
  }
}
?>

<script>
  window.ordersPortal = {
    idcliente: <?= (int) (
      $_SESSION['portal_idcliente']
      ?? 0
    ); ?>,

    idusuarioAcceso: <?= (int) (
      $_SESSION['portal_idusuario_acceso']
      ?? 0
    ); ?>,

    baseUrl: <?= json_encode(
      base_url(),
      JSON_UNESCAPED_UNICODE
      | JSON_UNESCAPED_SLASHES
    ); ?>
  };











</script>

</body>

</html>