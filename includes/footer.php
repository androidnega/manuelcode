<?php 
// Check if we're on the homepage (default to false if not set)
if (!isset($is_homepage)) {
    $is_homepage = (basename($_SERVER['PHP_SELF']) === 'index.php');
}
if (!$is_homepage): 
?>
<footer class="bg-[#2D3E50] text-white p-4 w-full mt-auto">
  <div class="w-full text-center">
    <p>&copy; <?php echo date('Y'); ?> ManuelCode. Professional Software Engineering Services. All rights reserved.</p>
  </div>
</footer>
<?php endif; ?>
</body>
</html>
