<?php
include "Methodes_Connexions.php";

session_start();

ConnexionRedirectionSuperAdmin();

$content_type = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : null;

header("content-type","application/json");

$method = $_SERVER['REQUEST_METHOD'];
if ( !in_array($method,array("POST","DELETE")) || $content_type != "application/json")
{
    http_response_code(405);
    echo '{ "message" : "Mauvaise methode" }';
    exit;
}

$body = http_get_request_body();

$jsn = json_decode($body);

if ( $jsn == NULL )
{
    http_response_code(400);
    echo '{ "message" : "Parametre(s) manquant(s)" , "table_name" : "'.$table_name.'" , "type" : "'.$type.'" }';
    exit;
}

switch ($method)
{
    case "DELETE": 
        $sql = "DELETE FROM TABLE parametrage WHERE Clef = '".$jsn['param_name']."'";
        $resultat = $bdd->query($sql);

        if ( $resultat !== false )
        {
            http_response_code(204);
            exit;
        }
        else
        {
            http_response_code(500);
            echo '{ "message" : "Erreur dans la BDD" , "param_name" : "'.$jsn['param_name'].'" }';
            exit;
        }
    break;

    case "POST":

        $success = 0;
        $failed = 0;
        $params = $jsn['params'];
        foreach($params as $key => $value)
        {
            $sql = "SELECT * FROM parametrage WHERE Clef = '".$key."'";
            $resultat = $bdd->query($sql);$resultat->closeCursor();

            if ($resultat !== false)
            {
                $sql = "UPDATE parametrage SET Valeur = '".$value['Valeur']."' WHERE Clef = '".$key."'";
                $resultat = $bdd->query($sql);
                if ( $resultat !== false )
                {
                    $success++;
                }
                else
                    $failed++;
            }
            else
            {
                $sql = "INSERT INTO parametrage (Clef,Valeur,Unite) VALUES ('".$key."' , '".$value['Valeur']."' , '".$value['Unite']."' )";
                $resultat = $bdd->query($sql);
                if ( $resultat !== false )
                {
                    $success++;
                }
                else
                    $failed++;
            }
        }

        http_response_code(200);
        echo '{ "success" : '.$success.' , "failed" : '.$failed.' }';
        exit;

    break;

}

?>
