<?php

include "Methodes_Connexions.php";

session_start();

ConnexionRedirectionBlank();

$cmd = isset( $_POST['mail'] ) ? $_POST['mail'] : null;

if ($cmd == null)
    exit;

if ( in_array($cmd,array('mail1','mail2','SMS') ) )
{
    $params_encoded_url = "User_id=".$_SESSION['Connexion_User']['User_id']."&cmd=".$cmd;
    $point_terminaison = "SendMailSMSTest";
    $url_final = 'http://127.0.0.1:84/BitFinexInterface/'.$point_terminaison;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url_final);
    curl_setopt($curl, CURLOPT_HEADER, false);      // ne renvoie pas le header dans la reponse
    $str = iconv("windows-1251", "UTF-8", $params_encoded_url);
    parse_str(urldecode($str), $json);
    $json = json_encode($json, JSON_UNESCAPED_UNICODE);
    curl_setopt($curl, CURLOPT_POSTFIELDS,$json);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curl);
    curl_close($curl);

    http_response_code(204);
}
else
    http_response_code(400);

?>