<?php
// Accès MariaDB : secrets dans /etc/coreen/database.php, hors du document root.
require_once __DIR__ . '/PHP_Extension_classe.php';

$configPath = '/etc/coreen/database.php';
if (!is_readable($configPath)) {
    error_log('[coreen] Fichier MariaDB absent ou illisible : ' . $configPath);
    http_response_code(503);
    exit('Configuration de la base de données indisponible.');
}

$config = require $configPath;
if (!is_array($config) || !isset($config['host'], $config['database'], $config['username'], $config['password']) ||
    !is_string($config['host']) || !is_string($config['database']) ||
    !is_string($config['username']) || !is_string($config['password'])) {
    error_log('[coreen] Format de configuration MariaDB incorrect.');
    http_response_code(503);
    exit('Configuration de la base de données invalide.');
}

$BDD_HOST = $config['host'];
$BDD = $config['database'];
$BDD_LOGIN = $config['username'];
$BDD_PWD = $config['password'];
unset($config); // Ne pas stocker le mot de passe dans la session.

try {
    $bdd = new MyPDO(
        'mysql:host=' . $BDD_HOST . ';dbname=' . $BDD . ';charset=utf8mb4',
        $BDD_LOGIN,
        $BDD_PWD
    );
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    error_log('[coreen] Échec de connexion MariaDB : ' . $e->getMessage());
    http_response_code(503);
    exit('Connexion à la base de données indisponible.');
}
unset($BDD_PWD); // Ne pas conserver la variable en mémoire au-delà de la connexion.

function All_to_POST()
{
    if ( in_array($_SERVER['REQUEST_METHOD'] ,array('PUT','DELETE','GET'))  )
    {
        parse_str( file_get_contents('php://input'), $_PARAMS);

        foreach($_PARAMS as $key => $value)
        {
            unset($_[$key]);
            $_PARAMS[str_replace('amp;','',$key)] = $value;
        }

        $_POST = $_PARAMS;
    }
}

function UPDATE_table_name_update($bdd,$t_name,$date_out)
{
    $sql = "SELECT count(*) N FROM table_date_update WHERE Table_name = '".$t_name."'";
    $resultat = $bdd->query($sql);
    if ($resultat !== false)
    {
        $row = $resultat->fetch();
        if ( $row['N'] == 0 )
        {
            // INSERT
            $sql = "INSERT INTO table_date_update (Table_name,Date,Date_out) VALUE ('".$t_name."' , '".date("Y-m-d H:i:s")."' , '".($date_out ==  null ? null : $date_out->format("Y-m-d H:i:s"))."' )";
            $resultat = $bdd->query($sql);
        }
        else
        {
            $sql = ($date_out == null) ? "UPDATE table_date_update SET Date = '".date("Y-m-d H:i:s")."' WHERE Table_name = '".$t_name."'" :
                "UPDATE table_date_update SET Date = '".date("Y-m-d H:i:s")."' , Date_out = '".$date_out->format("Y-m-d H:i:s")."' WHERE Table_name = '".$t_name."'" ;
            $resultat = $bdd->query($sql);
        }
    }
}

function Get_Parametrage($bdd,$param_name)
{
    $sql = "SELECT * FROM parametrage WHERE Clef = '".$param_name."'";
    $resultat = $bdd->query($sql);
    if ($resultat != false)
    {
        $row = $resultat->fetch();

        $r = isset( $row['Valeur'] ) ? $row['Valeur'] : null;

        $resultat->closeCursor();

        return $r;
    }

    return null;
}

function Is_Table_Exist($bdd,$table_name)
{

    switch($table_name)
    {
        case "table_date_update":

        //DROP TABLE IF EXISTS `table_date_update`;
        $sql = "CREATE TABLE IF NOT EXISTS `table_date_update` (
        `Table_name` varchar(64) NOT NULL,
        `Date` datetime(1) NOT NULL,
        `LOCK_User_id` int(11) DEFAULT NULL,
        `Date_out` datetime DEFAULT NULL,
        PRIMARY KEY (`Table_name`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

        $resultat = $bdd->query($sql);

        if ($resultat === false)
            return false;
            else
            return true;

        break;


    }

}

function UPDATE_table_name_update_CANDLES_OUT($bdd,$t_name)
{
    $sql = "SELECT * FROM table_date_update WHERE Table_name = '".$t_name."'";
    $resultat = $bdd->query($sql);
    if ($resultat != false)
    {
        $row = $resultat->fetch();$resultat->closeCursor();
        if ($row == false) return true;

        $date_out = new DateTime($row['Date_out']);
        $date_now = new DateTime();

        if ( $date_out < $date_now )    
        {
            return array( "time_out" => true , "date" => $row['Date'] , "date_out" => $date_out);
        }    

        return array( "time_out" => false , "date" => $row['Date'] , "date_out" => $date_out);
    }

    return array( "time_out" => true ); 
}

function UPDATE_table_name_update_OUT($bdd,$t_name)
{
    $sql = "SELECT * FROM table_date_update WHERE Table_name = '".$t_name."'";
    $resultat = $bdd->query($sql);

    if ($resultat !== false)
    {
        $row = $resultat->fetch();$resultat->closeCursor();
        if ($row == false) return true;

        $date_last = new DateTime($row['Date']);
        $date_out = $row['Date_out'] == null ? null : new DateTime($row['Date_out']);
        $date_now = new DateTime();

        if (  $date_out == null || $date_out < $date_now )
            return true;

//        $timespan_diff = $date_last->diff($date_now);
//        if ($timespan_diff->days > $timespan / 86400)
//        {
//            return true;
//        }

        return false;   
    }

    return true;
}

// sens 1 -> 2 -> 3 ou 1 -> 2 -> 4 ou 1 -> 2 -> 5
class UserStatut
{
    const NonConnecte = 1;
    const NonValider = 2;
    const Valider = 3;
    const Administrateur = 4;
    const SAdministrateur = 5;
}

function ConnexionUser()
{
    $r = isset($_SESSION['Connexion_User']) ? 
                    ( $_SESSION['Connexion_User']['Valider'] ? 
                                ( $_SESSION['Connexion_User']['SAdministrateur'] ? UserStatut::SAdministrateur : ( $_SESSION['Connexion_User']['Administrateur'] ? UserStatut::Administrateur : UserStatut::Valider)) : 
                    UserStatut::NonValider) : 
        UserStatut::NonConnecte;
    return $r;
}

function ConnexionUser_Detail()
{
        $statut = ConnexionUser();
        if ( $statut != UserStatut::NonConnecte )
        {
                echo "<span>Bonjour ".(in_array($statut,array(UserStatut::Administrateur,UserStatut::SAdministrateur)) ? '<span style="background:red;">'.($statut == UserStatut::SAdministrateur? 'SUPER':'').' '.'ADMINISTRATEUR</span>' : '')."   ".$_SESSION['Connexion_User']['Prenom']." ".$_SESSION['Connexion_User']['Nom']."<br/>\r\n";
                if ( isset($_SESSION['Connexion_User']) && isset($_SESSION['Connexion_User']['LastConnexion']) && $_SESSION['Connexion_User']['LastConnexion'] != NULL)
                {
                        $date_last = new DateTime($_SESSION['Connexion_User']['LastConnexion']);
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

                        echo "<span>Votre dernière connexion le ".$_SESSION['Connexion_User']['LastConnexion']."     il y a ".$str."</span><br/>\r\n";
                }
                        else
                        echo "<span>Première connexion.</span><br/>\r\n";

                if ( $statut == UserStatut::NonValider )
                        echo "<span>Votre compte n'est pas validé. Votre accès est restreint.</span><br/>\r\n";

                echo '<br/>'."\r\n";
                //Menus();
                echo '<br/><br/>'."\r\n";
        } 
}

function ConnexionRedirectionBlank()
{
    if ( $_SERVER['REMOTE_ADDR'] == "127.0.0.1" )
        return;

    if ( in_array(ConnexionUser(),array(UserStatut::NonConnecte,UserStatut::NonValider)) )
        {
            http_response_code(403);
            exit;
        }
}

function ConnexionRedirection()
{ 
    if ( in_array(ConnexionUser(),array(UserStatut::NonConnecte,UserStatut::NonValider)) )
    {
        echo '
        <span>Vous n\'etes pas autorisé à accéder à cette page.</span><br/>
        <span>Redirection vers la page de connexion dans </span>
        <span id="timer" ></span>

        <script type="text/javascript">
            
        var cmpt = 10;
        document.getElementById("timer").innerHTML = cmpt + " secondes";

        setInterval(function()
        {
            cmpt--;
            var o = document.getElementById("timer");
            o.innerHTML = cmpt + " secondes";

            if (cmpt <= 0)
                document.location = "Connexion.php";

        },1000);

        </script>
        </body>
        </html>
        ';

        http_response_code(401);
        exit;
    }
}

function ConnexionRedirectionSuperAdmin()
{
    if ( !in_array(ConnexionUser(),array(UserStatut::SAdministrateur)) ) 
    {
        echo '
        <span>Vous devez être Super Administrateur pour accéder à cette page.</span><br/>
        <span>Redirection vers la page de connexion dans </span>
        <span id="timer" ></span>

        <script type="text/javascript">
            
        var cmpt = 10;
        document.getElementById("timer").innerHTML = cmpt + " secondes";

        setInterval(function()
        {
            cmpt--;
            var o = document.getElementById("timer");
            o.innerHTML = cmpt + " secondes";

            if (cmpt <= 0)
                document.location = "Connexion.php";

        },1000);

        </script>
        </body>
        </html>
        ';

        http_response_code(401);
        exit;
    }
}

function ConnexionRedirectionAdmin()
{ 

    if ( !in_array(ConnexionUser(),array(UserStatut::Administrateur,UserStatut::SAdministrateur)) ) 
    {
        echo '
        <span>Vous devez être Administrateur pour accéder à cette page.</span><br/>
        <span>Redirection vers la page de connexion dans </span>
        <span id="timer" ></span>

        <script type="text/javascript">
            
        var cmpt = 10;
        document.getElementById("timer").innerHTML = cmpt + " secondes";

        setInterval(function()
        {
            cmpt--;
            var o = document.getElementById("timer");
            o.innerHTML = cmpt + " secondes";

            if (cmpt <= 0)
                document.location = "Connexion.php";

        },1000);

        </script>
        </body>
        </html>
        ';

        http_response_code(401);
        exit;
    }
}

function ConnexionRedirectionSAdmin()
{ 
    if ( ConnexionUser() != UserStatut::SAdministrateur) 
    {
        echo '
        <span>Vous devez être Super Administrateur pour accéder à cette page.</span><br/>
        <span>Redirection vers la page de connexion dans </span>
        <span id="timer" ></span>

        <script type="text/javascript">
            
        var cmpt = 10;
        document.getElementById("timer").innerHTML = cmpt + " secondes";

        setInterval(function()
        {
            cmpt--;
            var o = document.getElementById("timer");
            o.innerHTML = cmpt + " secondes";

            if (cmpt <= 0)
                document.location = "Connexion.php";

        },1000);

        </script>
        </body>
        </html>
        ';

        http_response_code(401);
        exit;
    }
}


function Menus()
{
    switch(ConnexionUser())
    {
        case UserStatut::SAdministrateur:
        case UserStatut::Administrateur:

            echo '      
            <style>

            .dropdown { background-color: #A0F0A080;position:relative; }

            .span { }

            .dropdown-content
            { 
                min-width:350px;
                background-color: #F0F0F000;
                display:none;
                position: absolute;
                z-index: 1;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); 
                transition-duration: 1.0s;
                transition-property: display;
            }

            .span:hover 
            { 
                background-color:#00FF00A0; 
            }

            .dropdown:hover .dropdown-content 
            { 
                display:block;
                width: auto;
                background-color: #F0F0A0FF;
            }
            
            .dropdown-content a:hover 
            { 
                background-color:#00FF00A0;
                color:#0000FFFF;
                transition-duration: 0.1s;
                transition-property: background-color,color;
            }

            .a
            {
            }

            </style>
            <div class="dropdown">
            <span class="span" >MENU</span>
            <div class="dropdown-content" >
            <a href="PrincipaleSuperAdmin.php" target="_blank">Page principale Super Administrateur</a><br/>
            <a href="PrincipaleAdmin.php" target="_blank">Page principale Administrateur</a><br/>
            <a href="Dev.php" target="_blank">Page de développement de Classes</a><br/>
            <a href="Connexion.php" target="_blank">Connexion des utilisateurs</a><br/>
            <a href="CreationUser.php" target="_blank">Voir le compte personnel</a><br/>
            <a href="Chargement_BDD.php" target="_blank">Chargement de la BDD</a> <br/>
            <a href="PassageOrdres.php" target="_blank">Passage Ordres Achat & Vente</a><br/>
            <a href="TestVolumeSimple_Evaluation.php" target="_blank">Simulation JSON maximum 1000 bougies</a><br/>
            <a href="TestVolumeSimple_EvaluationBDD.php" target="_blank">Simulation BDD ilimitée en nombre de bougies</a><br/>
            <a href="TestVolumeSimple_BarreProgression.php" target="_blank">Barre de progression</a><br/>
            <a href="OrdersBook.php" target="_blank">Livre des ordres Achats/Ventes</a><br/>
            <a href="OrdersBookAll.php" target="_blank">Livre des ordres Achats/Ventes de toutes les cryptomonaies</a><br/>
            <a href="ListeUsers.php" target="_blank">Liste des Utilisateurs</a><br/>
            <a href="flash_crack.php" target="_blank">Liste des Flashs Cracks</a>
            </div>
            </div>';

        break;

        case UserStatut::Valider:

            echo '      
            <style>

            .dropdown { position:relative;display: inline-block; }

            .span {  }

            .dropdown-content
            { 
                min-width:350px;
                background-color: #F0F0F000;
                display:none;
                position: absolute;
                z-index: 1;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); 
                transition-duration: 1s;
                transition-property: display;
            }

            .span:hover { background-color:#00FF00A0; }

            .dropdown:hover .dropdown-content 
            { 
                display:block;
                width: auto;
                background-color: #F0F0F0FF;
            }
            
            .dropdown-content a:hover 
            { 
                background-color:#00FF00A0;
                color:#0000FFFF;
                transition-duration: 0.3s;
                transition-property: background-color,color;
            }

            </style>
            <div class="dropdown">
            <span class="span" >MENU</span>
            <div class="dropdown-content" >
            <a href="Connexion.php" target="_blank">Connexion des utilisateurs</a><br/>
            <a href="CreationUser.php" target="_blank">Voir le compte personnel</a><br/>
            <a href="Chargement_BDD.php" target="_blank">Chargement de la BDD</a><br/>
            <a href="PassageOrdres.php" target="_blank">Passage Ordres Achat & Vente</a><br/> 
            <a href="TestVolumeSimple_Evaluation.php" target="_blank">Simulation JSON maximum 1000 bougies</a><br/>
            <a href="TestVolumeSimple_EvaluationBDD.php" target="_blank">Simulation BDD ilimitée en nombre de bougies</a><br/>  
            <a href="TestVolumeSimple_BarreProgression.php" target="_blank">Barre de progression</a><br/>
            <a href="OrdersBook.php" target="_blank">Livre des ordres Achats/Ventes</a><br/>
            <a href="flash_crack.php" target="_blank">Liste des Flashs Cracks</a>
            </div>
            </div>';

    }
}

?>