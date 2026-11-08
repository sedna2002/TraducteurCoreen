<?php
// Ancienne page remplacée : le filtrage et les droits sont traités dans Administration.php.
$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$url = 'Administration.php' . ($userId ? '?user_id=' . $userId : '') . '#historique';
header('Location: ' . $url, true, 302);
exit;
