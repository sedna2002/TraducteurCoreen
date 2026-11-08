<?php
// Ancienne API de graphique désactivée : remplaçée par les statistiques SQL sécurisées d'Administration.php.
http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
echo 'API historique remplacée par Administration.php';
