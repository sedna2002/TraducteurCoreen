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

    $to = "user1@example.com, user2@example.com";
    $subject = "Confirmation d'adresse email pour le 'Traducteur Coréen'";

    $message = "
    <html>
    <head>
    <title>Confirmez votre adresse email</title>
    </head>
    <body>
    <p>Test email. Please ignore.</p>
    </body>
    </html>
    ";

    // It is mandatory to set the content-type when sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    // More headers. From is required, rest other headers are optional
    $headers .= 'From: <info@example.com>' . "\r\n";
    $headers .= 'Cc: sales@example.com' . "\r\n";

    mail($to,$subject,$message,$headers);

    http_response_code(204);
}
else
    http_response_code(400);

?>