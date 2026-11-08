<?php
include "Methodes_Connexions.php";

session_start();

/*
login="+login+"&pw="+pw_md5+"&nom="+nom+"&prenom="+prenom+"&mail1="+mail1+"&mail2="+mail2+"&telephone="+telephone+ \
"&telephone_pw="+telephone_pw+"&telephone_check="+telephone_check+"&key_api="+key_api+"&secret_api="+secret_api+ \
"&mail1_check="+mail1_check+"&mail2_check="+mail2_check);
*/

$update = isset($_GET['update']);

$login = isset( $_POST['login'] ) ? $_POST['login'] : NULL;
$pw = isset( $_POST['pw'] ) ? $_POST['pw'] : NULL;
$nom = isset( $_POST['nom'] ) ? $_POST['nom'] : NULL;
$prenom = isset( $_POST['prenom'] ) ? $_POST['prenom'] : NULL;
$mail1 = isset( $_POST['mail1'] ) ? $_POST['mail1'] : NULL;
$mail2 = isset( $_POST['mail2'] ) ? $_POST['mail2'] : NULL;
$telephone = isset( $_POST['telephone'] ) ? $_POST['telephone'] : NULL;
$telephone_login = isset( $_POST['telephone_login'] ) ? $_POST['telephone_login'] : NULL;
$telephone_pw = isset( $_POST['telephone_pw'] ) ? $_POST['telephone_pw'] : NULL;
$key_api = isset( $_POST['key_api'] ) ? $_POST['key_api'] : NULL;
$secret_api = isset( $_POST['secret_api'] ) ? $_POST['secret_api'] : NULL;
$telephone_check = isset( $_POST['telephone_check'] ) ? $_POST['telephone_check'] : NULL;
$mail1_check = isset( $_POST['mail1_check'] ) ? $_POST['mail1_check'] : NULL;
$mail2_check = isset( $_POST['mail2_check'] ) ? $_POST['mail2_check'] : NULL;
$user_id = isset( $_SESSION['Connexion_User'] ) ?  $_SESSION['Connexion_User']['User_id'] : NULL;

$sql = "SELECT * FROM utilisateurs WHERE Login = '".$login."'";

$resultat = $bdd->query($sql);
    if ($resultat != false)
    {
        $row = $resultat->fetch(PDO::FETCH_ASSOC);
        if ( isset( $row['login'] ) ) 
        {
            http_response_code(400);
            echo "Le Login est déjà utilisé.";
            exit;
        }

        $resultat->closeCursor();
    }

    if (!$update)
    {
        $sql = "INSERT INTO utilisateurs (Login,Password,Nom,Prenom,Mail1,Mail2,Telephone,loginServiceSMSFree,
            clefServiceSMSFree,KeyAPI,SecretAPI,mail1Enable,mail2Enable,ServiceSMSFreeEnable,Date_cre,Administrateur,Valider) VALUES
            ('".$login."','".$pw."','".$nom."','".$prenom."','".$mail1."','".$mail2."','".$telephone."','".
            $telephone_login."','".$telephone_pw."','".$key_api."','".$secret_api."',".$mail1_check.",".
            $mail2_check.",".$telephone_check.",'".date("Y-m-d H:i:s")."',false,false)";

        $resultat = $bdd->query($sql);

        if ($resultat != false)
            echo "OK";
            else
            {
                http_response_code(400);
                echo "NOK".$sql;
            }
    }
    else
    {
        $sql = "UPDATE utilisateurs SET Nom = '".$nom."' , Prenom = '".$prenom."', Mail1 = '".$mail1."' ,
            Mail2 = '".$mail2."' , Telephone = '".$telephone."' , loginServiceSMSFree = '".$telephone_login."' ,
            clefServiceSMSFree = '".$telephone_pw."' , KeyAPI = '".$key_api."' , SecretAPI = '".$secret_api."' , 
            mail1Enable = ".$mail1_check." , mail2Enable = ".$mail2_check." , ServiceSMSFreeEnable = ".$telephone_check." , 
            Date_upd = '".date("Y-m-d H:i:s")."'  
            WHERE User_id = ".$user_id;

        $resultat = $bdd->query($sql);

        if ($resultat != false)
            echo "OK";
            else
            {
                http_response_code(400);
                echo "NOK".$sql;
            }

        $sql = "SELECT * FROM utilisateurs WHERE User_id = ".$user_id;
        $resultat = $bdd->query($sql);
        if ($resultat != false)
        {
            $row = $resultat->fetch(PDO::FETCH_ASSOC);
            foreach($row as $key => $value)
            {
                $_SESSION['Connexion_User'][$key] = $value;
            }
        }
    }
    
?>