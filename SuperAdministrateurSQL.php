<?php
// Ancienne API d'opérations SQL dangereuses, héritée d'un autre projet : désactivée dans l'application coréenne.
http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
echo 'Cette ancienne API a été désactivée.';
