<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Liste historique de connexion</title>
</head>

<body>

<form id="form1">

<style type="text/css">

 caption {
font-weight: bold;
font-size:16;
background:red; 
color:black;
 }
 
 table  {
 border-width:1px; 
 border-style:solid; 
 border-color:black;
 /* width:80%; */
 border-collapse: collapse; 
 text-align:center;
 margin:auto;
 font-size:14;
 /* display:; */
 }

 td {
border-width:1px;
border-style:solid; 
border-color:black;
border-collapse: collapse; 
 }
 
</style>

<?php
include "Methodes_Connexions.php";

session_start();

ConnexionRedirectionAdmin();

?>
<h2 style="text-align:center;">Liste historique de connexion</h2><br/>
<?php

echo '<br/>';
ConnexionUser_Detail();
echo '<br/>';

$User_id = isset( $_GET['user_id'] ) ? $_GET['user_id'] : null;

$sql = "SELECT * FROM historique WHERE User_id = ".$User_id." ORDER BY Date DESC";   

?>

<table>
<caption style="background:#FF000080">Liste des historiques de connexions</caption>
<thead>
    <tr style="background:#FFFF0080">
        <td>Date</td>
        <td>Il y a</td>
        <td>Adresse IP</td>
        <td>Host</td>
    </tr>
</thead>
<tbody>
<?php

$resultat = $bdd->query($sql);

if ($resultat != false)
{
    $n=0;
    while ($row = $resultat->fetch(PDO::FETCH_ASSOC))
    {
        echo '<tr>'."\r\n";
        echo '<td>'.$row['Date'].'</td>'."\r\n";
        
        $date_last = new DateTime($row['Date']);
        $date_now = new DateTime();
        $timeSpan = $date_last->diff($date_now);
        $f = "";
        $f .=  $timeSpan->y != 0 ? "%y an(s) " : "";
        $f .=  $timeSpan->m != 0 ? "%m mois " : "";
        $f .=  $timeSpan->d != 0 ? "%d j " : "";
        $f .=  $timeSpan->h != 0 ? "%h h " : "";
        $f .=  $timeSpan->i != 0 ? "%i min " : "";
        $f .=  $timeSpan->s != 0 ? "%s s " : "";

        $str = $timeSpan->format($f); //"%y an(s) %m mois %d j %h h %i min %s s");
        echo '<td>'.$str.'</td>'."\r\n";

        echo '<td>'.$row['AdresseIP'].'</td>'."\r\n";
        echo '<td>'.$row['Host'].'</td>'."\r\n";
        echo '</tr>'."\r\n";
    }

    $resultat->closeCursor();
}

?>
    <tr>
        
    </tr>
</tbody>
</table>

</form>
</body>
</html>
