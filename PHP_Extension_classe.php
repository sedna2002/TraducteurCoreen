<?php

    class MyPDO extends PDO
    {
    
        public function __construct ( string $dsn , string $username , string $password )
        {
            parent::__construct($dsn,$username,$password); 
        }
        
        // verifie si la colonne existe dans la table
        public function IsColumnExist($table,$colonne)
        {
            $sql = "SHOW COLUMNS FROM `".$table."` WHERE Field = '".$colonne."'";
            $resultat = $this->query($sql);// $this->prepare($sql);
            //$resultat->execute();
            return $resultat != false ? $resultat->rowCount() != 0 : false;
        }
        
        public function ReadVariable($nom)
        {   
            $sql = "SELECT * FROM GlobalVariable WHERE nom = '".$nom."'";
            $resultat = $this->query($sql);
            if ($resultat != false)
                { $row = $resultat->fetch(PDO::FETCH_ASSOC);$resultat->closeCursor();return $row['valeur']; }
            
            return null;
        }
        
        public function WriteVariable($nom,$valeur)
        {
            $sql = "SELECT * FROM GlobalVariable WHERE nom = '".$nom."'";
            $resultat = $this->query($sql);
            if ($resultat == false) return;
            
            if ($resultat->rowCount() != 0)
                {
                    $sql ="UPDATE GlobalVariable SET valeur = '".$valeur."' WHERE nom = '".$nom."'";
                    $this->query($sql);
                }
                else
                {
                    $sql ="INSERT INTO GlobalVariable (`nom`,`valeur`) VALUES ('".$nom."','".$valeur."')";
                    $this->query($sql);
                }
                
           $resultat->closeCursor();
        }
        
        public function DeleteVariable($nom)
        {
            $sql = "DELETE FROM GlobalVariable WHERE nom = '".$nom."'";
            $this->query($sql);
        }
        
        public function InitGlobalVariable()
            { 
                $sql = "CREATE TABLE IF NOT EXISTS `globalvariable` (
  `nom` varchar(64) NOT NULL,
  `valeur` varchar(1024) DEFAULT NULL,
  UNIQUE KEY `nom` (`nom`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1;";
                $this->query($sql);
            }
        
        public function IsTableExist($table)
        {
            $sql = "show tables where Tables_in_bittrex = ".$table;
            $resultat = $this->query($sql);
            return $resultat->rowCount() != 0;
        }
        
        
    }
    
    // $callback = function($key,$valeur,$scalaire_init) { return $var_init + $valeur; } pour faire la somme de tout les elements
    function array_scalaire( $callback , $array , $scalaire_init=0)
    {
    foreach($array as $key=>$valeur)
        {
            $scalaire_init = call_user_func($callback,$key,$valeur,$scalaire_init); 
        }
        
    return $scalaire_init;
    }
    
    class Changement
    {
        private $valeur = null;
        
        public function __construct($init)
        {
            $this->valeur = $init;
        }
        
        public function Actualise($valeur,$callback)
        {
            $a = $this->valeur !== $valeur;
            $this->valeur = $valeur;
            
            if ($a) call_user_func($callback); 
            
            return $a;
        }
    }


function AfficheTable($table_id,$bdd,$titre,$translate,$tables,$sql_select,$class = null)
{
    echo'
<table '.($table_id != null ? 'id="'.$table_id.'"' : '').' '.($class != null ? 'class="'.$class.'"' : '' ).' >
    <caption>'.$titre.'</caption>
    <thead>
        <tr>';

        $table_colonne = array();
        $n = 0;
        
    foreach($tables as $value)
    {
        $sql = "SHOW COLUMNS FROM bittrex.".$value;
        $resultat = $bdd->query($sql);
        if ($resultat != false)
        {
            while($row = $resultat->fetch(PDO::FETCH_ASSOC))
                $table_colonne[$n++] = $row;

                $resultat->closeCursor();
        }
    }
        $table_colonne_Field = array_column($table_colonne,"Field");

        foreach($translate as $key => $value)
            if ( is_array($value) )
                echo "<td ".(array_key_exists('display',$value) ? 'style="display:'.$value['display'].';"' : '')." >".$value['name']."</td>"."\r\n";
                else
                echo "<td>".$value."</td>"."\r\n";

echo'
        </tr>
    </thead>
    <tbody>';

    $resultat = $bdd->query($sql_select);
    if ($resultat != false)
    {
        while($row = $resultat->fetch(PDO::FETCH_ASSOC))
        {
            $n = 0;
            echo "<tr>"."\r\n";
                foreach($translate as $key => $value)
                {
                    $style = is_array($value) ? ( array_key_exists('display',$value) ? 'style="display:'.$value['display'].'"' : '' ) : '';

                    if ( array_key_exists($key,$row) )
                    {
                        $s = null;
                        foreach($table_colonne as $k => $v) if ( $v['Field'] == $key ) { $s = $v;break; }

                        if ( $s != null && $s["Type"] == "tinyint(1)" )
                            echo '<td '.$style.'><input type="checkbox" disabled '.( $row[$key] == 1 ? 'checked' : '')."/></td>"."\r\n";
                            else
                            echo '<td '.$style.'>'.$row[$key]."</td>"."\r\n";
                    }
                }
            echo "</tr>"."\r\n";
        }

        $resultat->closeCursor();
    }
echo'
    </tbody>
</table>';
}
        
?>