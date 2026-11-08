<?php
include "Methodes_Connexions.php";

session_start();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Liste des Utlisateurs</title>
</head>

<body>

<style>

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
 font-size:12;
 /* display:; */
 }
 
 td {
border-width:1px;
border-style:solid; 
border-color:black;
border-collapse: collapse; 
 }

 rect {
    fill:#FF000080;
    stroke-width:1;
    stroke:#FF0000FF;
 }

 rect:hover {
    fill:#FFFF0080;
    stroke-width:5;
    stroke:#FF000080;
 }

</style>

<form id="form1">

<?php

echo "<h2 style=\"text-align:center;\">Liste des Utilisateurs</h2><br/>"."\n\r";

ConnexionRedirectionAdmin();
ConnexionUser_Detail();

$svg_larg = 1024;
$svg_haut = 300;

?>
<br/>
<br/>
<table>
<caption style="background:red; color:black;">Liste des utilisateurs</caption>
    <thead style="background:yellow">
        <tr>
            <?php
            $colonnes = array ();
            //$sql = "SHOW COLUMNS FROM utilisateurs";
            $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE table_schema='vocabulairecoreen' 
                    AND table_name='utilisateurs' ";

            $resultat = $bdd->query($sql);

            if ($resultat != false)
            {
                $n=0;
                while ($row = $resultat->fetch(PDO::FETCH_ASSOC))
                {
                    $colonnes[$n++] = $row;
                    $private = $row['COLUMN_COMMENT'] == 'PRIVATE';
                    echo '<td '.( $private ? 'style="background:#FF000060;"':'' ).' >'.$row['COLUMN_NAME'].'</td>'."\r\n";
                }

                echo '<td>Action</td>';
            }
            
            ?>
        </tr>
    </thead>
    <tbody>
            <?php

            $line = 0;
            $sql = "SELECT * FROM utilisateurs";
            $resultat = $bdd->query($sql);
            
            if ($resultat != false)
            {
                while ($row = $resultat->fetch(PDO::FETCH_BOTH))
                {        
                    echo '<tr>'."\r\n";
                    for($n=0;$n < count($colonnes);$n++)
                    {
                        $readonly = $row['User_id'] == $_SESSION['Connexion_User']['User_id'] || ConnexionUser() != UserStatut::SAdministrateur || !in_array($colonnes[$n]['COLUMN_NAME'],array('Administrateur','SAdministrateur'));
                        $private = $colonnes[$n]['COLUMN_COMMENT'] == 'PRIVATE';
                        $checkbox = $colonnes[$n]['COLUMN_TYPE'] == 'tinyint(1)';
                        if ($checkbox)
                            echo '<td '.($line & 1 ? 'style="background:#80808080"' : '').' ><input type="checkbox" '.($readonly ? 'disabled' : '').' '.($private ? '' : ($row[$n] != 0 ? 'checked' :'' )).' id="'.$colonnes[$n]['COLUMN_NAME'].'_'.$n.'" onchange="update('.$row['User_id'].')" /></td>'."\r\n";
                            else
                            echo '<td '.($line & 1 ? 'style="background:#80808080"' : '').' >'.($private ? str_repeat ('*',strlen($row[$n])) : $row[$n]).'</td>'."\r\n";
                    }

                    echo '<td><a href="HistoriqueConnexion.php?user_id='.$row['User_id'].'" target="_blank">Historique de connexion</a></td>'."\r\n";  

                    $line++;
                    echo '</tr>'."\r\n";
                }
            }

            ?>
    </tbody>
</table>

<br/>
<br/>
<?php
$date = new DateTime();

echo '<div style="text-align:center;" >'."\r\n";
echo '<input  type="button" value="<<<" onclick="date_chg(-1)" />
<span id="span_date" >'.$date->format('Y-m').'</span>
<input type="button" value=">>>"  onclick="date_chg(1)" />'."\r\n";

?>
<br/>

<svg id="svg_id" width="<?php echo $svg_larg; ?>" height="<?php echo $svg_haut; ?>" style="background:black;">
<?php
        
        $jours_semaine = array("Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi");

        $intervalle_1j = new DateInterval("P1D");
        $intervalle_1m = new DateInterval("P1M");

        
        $date_deb = (new DateTime())->setDate($date->format('Y'),$date->format('m'),1)->setTime(0,0,0);
        $n_jours_mois_max =(int) $date_deb->add($intervalle_1m)->sub($intervalle_1j)->format('d');
        $date_deb = (new DateTime())->setDate($date->format('Y'),$date->format('m'),1)->setTime(0,0,0);
        $ech_x = $svg_larg / $n_jours_mois_max;
//echo $date_deb->format('Y-m-d');
//echo '  '.$n_jours_mois_max;
        $sql = "select max(N) \"MAX_N\" from (SELECT count(*) \"N\" FROM historique WHERE year(Date) = ".$date_deb->format('Y')." AND month(Date) = ".$date_deb->format('m')." GROUP BY day(Date) ) t";
        $resultat = $bdd->query($sql);
        
        if ($resultat != false)
        {
            $row = $resultat->fetch(PDO::FETCH_ASSOC);
            $max_N = $row['MAX_N'];

            $ech_y = $max_N == 0 ? 0 : ($svg_haut * 0.9) / $max_N;

            $sql = "SELECT count(*) \"N\",DATE_FORMAT(Date,\"%Y-%m-%d\") \"Date\",day(Date) \"day\" FROM historique WHERE year(Date) = ".$date_deb->format('Y')." AND month(Date) = ".$date_deb->format('m')." GROUP BY day(Date) ORDER BY \"day\" ";
            $resultat = $bdd->query($sql);
            if ($resultat != false)
            {
                $row = $resultat->fetch(PDO::FETCH_BOTH);
                
                for($date_i = $date_deb,$i = 0;$i < 31 && $date_deb->format('m') == $date_i->format('m');$i++,$date_i = $date_i->add($intervalle_1j))
                {
                    $j_sem = $date_i->format('w');
                    echo '<text fill="#FFFFFFFF" font-size="10" style="writing-mode:vertical-lr;" font-family="Verdana" x="'.(($i+0.5) * $ech_x).'" y="'.($svg_haut - 12 * 10).'" >'.$jours_semaine[$j_sem].' '.$date_i->format('Y-m-d').'</text>';

                    if ((int) $date_i->format('d') > (int) $row['day'])
                        $row = $resultat->fetch(PDO::FETCH_BOTH);

                    $n = (int)$row['day'] == (int)$date_i->format('d') ? $row['N'] : 0;
                    echo '<rect x="'.($i * $ech_x).'" y="'.($svg_haut - $ech_y * $n).'" width="'.$ech_x.'" height="'.($svg_haut).'" style="" />'."\r\n";
                }
            }
        }
?>
</svg>
</div>

</form>

<script type="text/javascript" src="Date.js" ></script>
<script type="text/javascript" src="Classes.js"></script>
<script type="text/javascript" src="Graphique.js" ></script>
<script type="text/javascript" src="XHR.js"></script>
<script type="text/javascript">

var date_calendrier = new Date();
date_calendrier.setDate(1);
date_calendrier.setHours(0);
date_calendrier.setMinutes(0);
date_calendrier.setSeconds(0);

function update(user_id)
{
    var table = document.getElementsByTagName('table');
    var tbody = table[0].getElementsByTagName('tbody');
    var tr = tbody[0].getElementsByTagName('tr');

    for(var i=0;i < tr.length;i++)
    {
        //var td = 
    }
}

function date_chg(t)
{
    date_calendrier.setMonth(date_calendrier.getMonth() + t);
    document.getElementById('span_date').innerHTML = date_calendrier.format('Y-M');

    var svg = document.getElementById('svg_id');
    var max = 0;
    while (svg.firstChild) { svg.removeChild(svg.firstChild); }

    RequeteURL("ListeUsers_ajax","year="+(date_calendrier.getYear() - 100 + 2000)+"&month="+(date_calendrier.getMonth()+1),"POST",function(json) 
    {
        max = json.length > 0 ? json.MyMax(function (e) { return e.N; }).valeur : 0;

        var date_mois = date_calendrier.clone();
        date_mois.setMonth(date_calendrier.getMonth() + 1);
        date_mois.setDate(0);
        var jours_n = date_mois.getDate();
        var ech_x = svg.width.animVal.value / jours_n;
        var svg_haut = svg.height.animVal.value * 0.9;
        var i = 0;

        for(date_mois = date_calendrier.clone(),i = 0;i <= jours_n;i++,date_mois.setDate(date_mois.getDate() + 1))
        {
            var text = document.createElementNS("http://www.w3.org/2000/svg","text");

            text.setAttribute('fill',"#FFFFFFFF");
            text.setAttribute('font-size',10);
            text.style.writingMode = "vertical-lr";
            text.setAttribute('font-family',"Verdana");
            text.setAttribute('x',parseInt(ech_x * (i + 0.5)));
            text.setAttribute('y',parseInt(svg.height.animVal.value - 12 * 10));
            text.innerHTML = date_mois.format('l Y-M-D');

            svg.appendChild(text);
            var json_element = json.findIndex(function (element, index, array) { return element["day"] == (i+1); } );

            if ( json_element != -1 )
            {
                        var json_element = json[json_element];
                        var rect = document.createElementNS("http://www.w3.org/2000/svg","rect");
                        var n = json_element.N;
                        rect.setAttribute("x",i * ech_x);
                        var y = svg.height.animVal.value - svg_haut * n / max;
                        rect.setAttribute("y",y);
                        rect.setAttribute("width",ech_x);
                        rect.setAttribute("height",svg.height.animVal.value - y);
                        rect.setAttribute("style","backcolor:#FF808080");

                        svg.appendChild(rect);
            }
        }

        },true);
}

function RequeteURL(url,body,method,f_callback,asynchrone)
{
    var xhr = getXMLHttpRequest();
    url = /*"http://localhost/trading_bitfinex/" +*/ url;
    xhr.open(method, url , asynchrone);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.addEventListener('readystatechange', function() {
            if ( xhr.readyState == 4 )
            {
                if ( xhr.status == 200 ) //|| xhr.status == 0 ))
                    {      
                        if (f_callback != null)
                        {
                            var json = JSON.parse(xhr.responseText);
                            f_callback(json);
                        }
                    }    
            }
        });

    xhr.send(body);
}

</script>

</body>
</html>

