<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Connexion des utilisateurs</title>
</head>

<body>

<style type="text/css">

table  {
border-width:0px; 
border-style:solid; 
border-color:black;
/* width:80%; */
border-collapse: collapse; 
text-align:center;
margin:auto;
font-size:14;
/* display:; */
}

</style>

<form id="form1">

<h2 style="text-align:center;">Connexion des utilisateurs</h2><br/>
<?php
include "Methodes_Connexions.php";

session_start();

if( ConnexionUser() == UserStatut::NonConnecte )
{

?>
<br/>
<br/>
<div style="text-align:center;">
<table >
<tr>
        <td style="text-align:right;" ><span>Login</span></td>
        <td style="text-align:left;"><input type="text" value="" id="login" /></td>
</tr>
<tr>     
        <td style="text-align:right;"><span>Mot de passe</span></td>
        <td style="text-align:left;"><input type="password" value="" id="PW" />
        <span>Afficher le PW</span><input type="checkbox" id="afficherPW_id" onchange="afficherPW()" </td>
</tr>
</table>

<br/>
<br/>
<input type="button" style="background:#FF000080;" id="connect" onclick="connexion()" value="Connexion" />
<br/>
<br/>
<a href="CreationUser.php" target="_blank">Créer un compte</a>  

</div>

<?php

}
else
{
        echo "<br/>\r\n";
        ConnexionUser_Detail();
        ConnexionRedirectionBlank();
?>

<input type="button" value="Deconnexion" style="background:#FFAA0080" onclick="deconnexion()" />
<br/>
<a href="CreationUser.php" target="_blank">Modifier son compte</a>  

<?php
}
?>

</form>

<script type="text/javascript" src="md5.js"></script>
<script type="text/javascript" src="XHR.js"></script>
<script type="text/javascript">

function afficherPW()
{
        var o = document.getElementById('afficherPW_id').checked;

        if (!o)
                document.getElementById('PW').type = "password";
        else
                document.getElementById('PW').type = "text";
}

function connexion()
{
        var pw = document.getElementById('PW').value;
        var login = document.getElementById('login').value;
        var pw_md5 = hex_md5(pw);

        var xhr = getXMLHttpRequest();
        var url = "Connexion_Verification.php";
        xhr.open("POST", url , true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.addEventListener('readystatechange', function() {
        if (xhr.readyState == 4 && xhr.status >= 200) //|| xhr.status == 0 ))
                {      
                        connect_rep = xhr.responseText;
                        
                        if (connect_rep == "OK")
                                window.location.reload();
                                else
                                alert("Le login/mot de passe ne correspondent pas");
                }
        });

        xhr.send("login="+login+"&pw="+pw_md5);
}

function deconnexion()
{
        var xhr = getXMLHttpRequest();
        var url = "Deconnexion.php";
        xhr.open("GET", url , false);
        xhr.send(null);

        window.location.reload();
}

</script>

</body>

</html>