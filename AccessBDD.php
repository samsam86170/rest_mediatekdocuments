<?php
include_once("ConnexionPDO.php");

/**
 * Classe de construction des requêtes SQL à envoyer à la BDD
 */
class AccessBDD {
	
    public $login="root";
    public $mdp="Azerty85!";
    public $bd="mediatek86";
    public $serveur="localhost";
    public $port="3308";	
    public $conn = null;

    /**
     * constructeur : demande de connexion à la BDD
     */
    public function __construct(){
        try{
            $this->conn = new ConnexionPDO($this->login, $this->mdp, $this->bd, $this->serveur, $this->port);
        }catch(Exception $e){
            throw $e;
        }
    }

    /**
     * récupération de toutes les lignes d'une table
     * @param string $table nom de la table
     * @return lignes de la requete
     */
    public function selectAll($table){
        if($this->conn != null){
            switch ($table) {
                case "livre" :
                    return $this->selectAllLivres();
                case "dvd" :
                    return $this->selectAllDvd();
                case "revue" :
                    return $this->selectAllRevues();
                case "exemplaire" :
                    return $this->selectAllExemplairesRevue();
                case "commandedocument" :
                    return $this->selectAllCommandesDocument();
                default:
                    // cas d'un select portant sur une table simple, avec tri sur le libellé
                    return $this->selectAllTableSimple($table);
            }			
        }else{
            return null;
        }
    }

    /**
     * récupération d'une ligne d'une table
     * @param string $table nom de la table
     * @param string $id id de la ligne à récupérer
     * @return ligne de la requete correspondant à l'id
     */	
    public function selectOne($table, $id){
        if($this->conn != null){
            switch($table){
                case "exemplaire" :
                    return $this->selectAllExemplairesRevue($id);
                case "dvd" :
                    return $this->selectAllDvd($id);
                case "revue" :
                    return $this->selectAllRevues($id);
                case "commandedocument" :
                    return $this->selectAllCommandesDocument($id);
                default:
                    // cas d'un select portant sur une table simple			
                    $param = array(
                        "id" => $id
                    );
                    return $this->conn->query("select * from $table where id=:id;", $param);					
            }				
        }else{
                return null;
        }
    }
   
    /**
     * récupération de toutes les lignes de d'une table simple (sans jointure) avec tri sur le libellé
     * @param type $table
     * @return lignes de la requete
     */
    public function selectAllTableSimple($table){
        $req = "select * from $table order by libelle;";		
        return $this->conn->queryAll($req);		
    }

    /**
     * récupération de toutes les lignes de la table Livre et les tables associées
     * @return lignes de la requete
     */
    public function selectAllLivres(){
        $req = "select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $req .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $req .= "from livre l join document d on l.id=d.id ";
        $req .= "join genre g on g.id=d.idGenre ";
        $req .= "join public p on p.id=d.idPublic ";
        $req .= "join rayon r on r.id=d.idRayon ";
        $req .= "order by titre ";		
        return $this->conn->queryAll($req);
    }	

    /**
     * récupération de toutes les lignes de la table DVD et les tables associées
     * @return lignes de la requete
     */
    public function selectAllDvd(){
        $req = "select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $req .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $req .= "from dvd l join document d on l.id=d.id ";
        $req .= "join genre g on g.id=d.idGenre ";
        $req .= "join public p on p.id=d.idPublic ";
        $req .= "join rayon r on r.id=d.idRayon ";
        $req .= "order by titre ";	
        return $this->conn->queryAll($req);
    }	

    /**
     * récupération de toutes les lignes de la table Revue et les tables associées
     * @return lignes de la requete
     */
    public function selectAllRevues(){
        $req = "select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $req .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $req .= "from revue l join document d on l.id=d.id ";
        $req .= "join genre g on g.id=d.idGenre ";
        $req .= "join public p on p.id=d.idPublic ";
        $req .= "join rayon r on r.id=d.idRayon ";
        $req .= "order by titre ";
        return $this->conn->queryAll($req);
    }	

    /**
     * récupération de tous les exemplaires d'une revue
     * @param string $id id de la revue
     * @return lignes de la requete
     */
    public function selectAllExemplairesRevue($id){
        $param = array(
                "id" => $id
        );
        $req = "select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $req .= "from exemplaire e join document d on e.id=d.id ";
        $req .= "where e.id =:id ";
        $req .= "order by e.dateAchat DESC";		
        return $this->conn->queryAll($req, $param);
    }
    
    /**
     * récupération de toutes les commandes d'un document
     * @param string $id id du document concerné
     * @return lignes de la requete
     */
    public function selectAllCommandesDocument($id) {
        $param = array (
            "id" => $id
                );
        $req = "Select l.nbExemplaire, l.idLivreDvd, l.idSuivi, s.libelle, l.id, max(c.dateCommande) as dateCommande, sum(c.montant) as montant ";
        $req .= "from commandedocument l join suivi s on s.id=l.idSuivi ";
        $req .= "left join commande c on l.id=c.id ";
        $req .= "where l.idLivreDvd = :id ";
        $req .= "group by l.id ";
        $req .= "order by dateCommande DESC";
        return $this->conn->query($req, $param);
    }

    /**
     * suppresion d'une ou plusieurs lignes dans une table
     * @param string $table nom de la table
     * @param array $champs nom et valeur de chaque champs
     * @return true si la suppression a fonctionné
     */	
    public function delete($table, $champs){
        if($this->conn != null){
            // construction de la requête
            $requete = "delete from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);   
            return $this->conn->execute($requete, $champs);		
        }else{
            return null;
        }
    }
    
    /**
     * ajout d'une ligne dans une table
     * @param string $table nom de la table
     * @param array $champs nom et valeur de chaque champs de la ligne
     * @return true si l'ajout a fonctionné
     */	
    public function insertOne($table, $champs){
        if($this->conn != null && $champs != null){
            // construction de la requête
            $requete = "insert into $table (";
            foreach ($champs as $key => $value){
                $requete .= "$key,";
            }
            // (enlève la dernière virgule)
            $requete = substr($requete, 0, strlen($requete)-1);
            $requete .= ") values (";
            foreach ($champs as $key => $value){
                $requete .= ":$key,";
            }
            // (enlève la dernière virgule)
            $requete = substr($requete, 0, strlen($requete)-1);
            $requete .= ");";	
            return $this->conn->execute($requete, $champs);		
        }else{
            return null;
        }
    }

    /**
     * modification d'une ligne dans une table
     * @param string $table nom de la table
     * @param string $id id de la ligne à modifier
     * @param array $param nom et valeur de chaque champs de la ligne
     * @return true si la modification a fonctionné
     */	
    public function updateOne($table, $id, $champs){
        if($this->conn != null && $champs != null){
            // construction de la requête
            $requete = "update $table set ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key,";
            }
            // (enlève la dernière virgule)
            $requete = substr($requete, 0, strlen($requete)-1);				
            $champs["id"] = $id;
            $requete .= " where id=:id;";				
            return $this->conn->execute($requete, $champs);		
        }else{
            return null;
        }
    }

}