<?php
include "Methodes_Connexions.php";

session_start();

ConnexionRedirectionSuperAdmin();

$method = $_SERVER['REQUEST_METHOD'];
if ($method != "POST")
{
    http_response_code(405);
    echo '{ "message" : "Mauvaise methode" }';
    exit;
}

$table_name = isset($_POST['table_name']) ? $_POST['table_name'] : NULL;
$type = isset($_POST['type']) ? $_POST['type'] : NULL;

header("content-type","application/json");

if ( $table_name == NULL || $type == NULL)
{
    http_response_code(400);
    echo '{ "message" : "Parametre(s) manquant(s)" , "table_name" : "'.$table_name.'" , "type" : "'.$type.'" }';
    exit;
}

switch ($type)
{
    case "DROP" : $sql = "DROP TABLE ".$table_name; break;
    default:
    {
        http_response_code(400);
        echo '{ "message" : "Paramètre NON défini TYPE '.$type.'" , "table_name" : "'.$table_name.'" }';
        exit;
    }

}

    $resultat = $bdd->query($sql);$resultat->closeCursor();

    if ($resultat !== false)
    {
        http_response_code(204);
        exit;
    }
    else
    {
        http_response_code(500);
        echo '{ "message" : "Erreur dans la BDD" , "table_name" : "'.$table_name.'" , "type" : "'.$type.'" }';
        exit;
    }

?>
