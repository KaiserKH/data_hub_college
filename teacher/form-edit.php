<?php
// form-edit.php - Redirect to form-create.php with id parameter
header('Location: form-create.php?id=' . urlencode($_GET['id'] ?? ''));
exit;
?>
