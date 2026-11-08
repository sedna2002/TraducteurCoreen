<?php
include "Methodes_Connexions.php";

session_start();

ConnexionRedirectionBlank();

$year = isset( $_POST['year'] ) ? strtoupper( $_POST['year'] ) : null;
$month = isset( $_POST['month'] ) ? strtoupper( $_POST['month'] ) : null;

if ( $month == NULL || $year == NULL )
{
    http_response_code(400);
    echo "Parametre(s) manquant(s)";
    exit;
}

$sql = 'SELECT count(*) "N",DATE_FORMAT(Date,"%Y-%m-%d") "Date",day(Date) "day" FROM historique WHERE year(Date) = '.$year.' AND month(Date) = '.$month.' GROUP BY day(Date) ORDER BY "day" ';

$resultat = $bdd->query($sql);
if ($resultat != false)
{
    $json = "";
    
    while ($row = $resultat->fetch(PDO::FETCH_ASSOC))
    {
        $json .= '{"N":'.$row["N"].',"Date":"'.$row["Date"].'","day":'.$row["day"].'}';
        $json .= ',';        
    }

    $json = substr($json,0,-1);
    http_response_code(200);
    echo "[".$json."]";
}
else
{
    http_response_code(400);
    echo "Requete SQL incorrecte";
}
?>