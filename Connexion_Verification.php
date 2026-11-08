<?php
include "Methodes_Connexions.php";

session_start();

$login = isset($_POST['login']) ? $_POST['login'] : null;
$pw_md5 = isset($_POST['pw']) ? $_POST['pw'] : null;
if ($login == null || $pw_md5 == null)
{
    http_response_code(400);
    echo 'NOK';
    exit;
}

$sql = "SELECT * FROM utilisateurs WHERE Login = '".$login."' AND Password = '".$pw_md5."'";

$resultat = $bdd->query($sql);
	if ($resultat != false)
	{	
        
        $row = $resultat->fetch(PDO::FETCH_ASSOC);
        if ( isset( $row['Login'] ) ) 
        {   	
            foreach($row as $key => $value)
            {
                $_SESSION['Connexion_User'][$key] = $value;
            }

            $sql = "SELECT * from historique WHERE User_id = ".$row['User_id']." ORDER BY Date DESC limit 1";
            $resultat2 = $bdd->query($sql);
            
            if ($resultat2 != false)
            {
                $row = $resultat2->fetch(PDO::FETCH_ASSOC);
                if ( isset( $row['Date'] ) ) 
                {
                    $_SESSION['Connexion_User']['LastConnexion'] = $row['Date'];
                }
                else
                    $_SESSION['Connexion_User']['LastConnexion'] = NULL;

                $resultat2->closeCursor();
            }

            $sql = "INSERT INTO historique (User_id,Date,AdresseIp,Host) values (".
                $_SESSION['Connexion_User']['User_id'].",'".date("Y-m-d H:i:s")."','".$_SERVER['REMOTE_ADDR']."','".$_SERVER['HTTP_USER_AGENT']."')";

            $resultat3 = $bdd->query($sql);
            http_response_code(200);
            echo 'OK';
        }
        else
        {
            http_response_code(400);
            echo 'NOK';
            exit;
        }

    $resultat->closeCursor();
    }
    else
    {
        http_response_code(400);
        echo 'NOK';
        exit;
    }

    
?>