<?php $mainJsVersion = @filemtime(__DIR__ . '/../assets/js/main.js') ?: 1; ?>
<script src="../assets/js/main.js?v=<?= $mainJsVersion ?>" defer></script>
</body>
</html>
