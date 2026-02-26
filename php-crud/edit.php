<?php
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$_GET['route'] = 'resources/edit/' . $id;
require __DIR__ . '/index.php';

