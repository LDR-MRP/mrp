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
<?php
if (!empty($data['page_functions_js']) && is_array($data['page_functions_js'])) {
  foreach ($data['page_functions_js'] as $js) {
    echo '<script src="' . media() . '/js/' . $js . '"></script>' . PHP_EOL;
  }
}
?>

</body>

</html>