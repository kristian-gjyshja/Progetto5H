<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<?php $mainJsVersion = @filemtime(__DIR__ . '/../assets/js/main.js') ?: 1; ?>
<script src="../assets/js/main.js?v=<?= $mainJsVersion ?>" defer></script>
</body>
</html>
