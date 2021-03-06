<?php

require_once('modules/mod_etudiant/modele/modele_etudiant.php');
require_once('modules/mod_etablissement/cont_etablissement.php');
require_once('modules/mod_generique/controleur_generique.php');

class ContEtudiant extends ContGenerique{

    private $modeleEtud;
    private $contEtab;

    public function __construct () {
        parent::__construct();
        $this->modeleEtud = new ModeleEtudiant();
        $this->contEtab = new ContEtablissement();
    }


    public function fichier_image($file){
        $img=NULL;
        $erreur = null;
        if(!empty($file)) {
            $file_name = $file['img']['name'];
            $file_tmp_name = $file['img']['tmp_name'];
            $file_dest = 'modules/mod_etudiant/photos/'.$file_name;
            $file_extension = strrchr($file_name, ".");
        
            $extensions_autorises = array('.png','.jpg','.jpeg','.gif','.PNG','.JPG','.JPEG','.GIF');
            if(in_array( $file_extension, $extensions_autorises)) {
                if (move_uploaded_file($file_tmp_name, $file_dest)) {
                    if($_FILES['img']['error'] == 0) {
                        $img = $file_dest;
                    }
                    else{
                        $erreur = 'erreur nom de l\'image non attribué dans la BD' ;
                    }
                }
                else
                    $erreur = 'Erreur lors de la récupération de l\'image';
            }
            else {
                $erreur = null;
                //$erreur = 'On ne peut pas uploader un fichier qui n\'est pas une image !';
            }
        }

    if($erreur != null){
        if($_GET['action'] == 'ajouterEtudiant')
            throw new formAjoutEtudException($erreur);
        else
            throw new formModifEtudException($erreur);
    }
        return $img;
    } 

    function form_addEtud(){
        $action = 'ajouterEtudiant';
        $photoEtud = 'bootstrap-icons/icons/person.svg';
        $numApo = $nomEtud = $prenom = $dateNaiss = $courriel = $tel = $adrr1 = $adrr2 = $AnneePromo = $situationActu = $etablissements = "";
        $groupes = $this->modeleEtud->get_groupes();
        require('modules/mod_etudiant/vue_etudiant/formEtud.php');
    }

    function add_Student(){
        if(empty(htmlspecialchars($_POST['nom'])) || empty(htmlspecialchars($_POST['prenom'])))
            throw new formAjoutEtudException("Impossible d'ajouter l'étudiant. Le nom et le prénom ne sont pas définis");

        if($this->modeleEtud->student_existBD(htmlspecialchars($_POST['nom']),htmlspecialchars($_POST['prenom'])) != 0)
            throw new formAjoutEtudException('Impossible d\'ajouter l\'étudiant. Le nom et le prénom ont déjà été attribué à un étudiant. ['.htmlspecialchars($_POST['nom']).' : '.htmlspecialchars($_POST['prenom']).']');

            $photoEtud = self::fichier_image($_FILES);
        
        $idEtud = $this->modeleEtud->add_studentBD(htmlspecialchars($_POST['numApogee']),$photoEtud,htmlspecialchars($_POST['nom']),htmlspecialchars($_POST['prenom']),htmlspecialchars($_POST['dateNaiss']),
        htmlspecialchars($_POST['courriel']),htmlspecialchars($_POST['tel']),htmlspecialchars($_POST['adr1']),htmlspecialchars($_POST['adr2']),htmlspecialchars($_POST['anneePromotion']),htmlspecialchars($_POST['situationActuelle']));

        $this->modeleEtud->add_etud_groupe($idEtud,$_POST['selectGroupe']);
        $this->contEtab->add_etablissementEtud($idEtud);
        
        self::back_toPage();
        $message = 'ETUDIANT AJOUTE';
        require('modules/mod_etudiant/vue_etudiant/notification.php');
    }
  
    function back_toPage(){
        if(isset($_POST['pageRetour'])){
            if($_POST['pageRetour'] == "ajouterEtudiant"){
                self::form_addEtud();
            }
            elseif($_POST['pageRetour'] != "listeEtud"){
                self::form_updateEtud();
            }
            else{
                self::list_Student(0);
            }
        }
        else{
            self::list_Student(0);
        }
    }
  
    function list_Student($idGroupe){
        $nbEtudiants = $this->modeleEtud->nb_students();
        $nomGroupe = $this->modeleEtud->get_groupe_name($idGroupe);
        $nbPagesTotales = ceil($nbEtudiants/5);
        
        if (isset($_GET['page']) and !empty($_GET['page'])) {
            $page = $_GET['page'];

            $listEtudiant = $this->modeleEtud->get_Students($page, $idGroupe);

            require('modules/mod_etudiant/vue_etudiant/listEtud.php');
        }
        else {
            $page = 1;
            $listEtudiant = $this->modeleEtud->get_Students($page, $idGroupe);

            require('modules/mod_etudiant/vue_etudiant/listEtud.php');
        }
        
    }

    function form_updateEtud(){
        //on initialise les variables pour remplir les champs du formulaire
        $action = 'modifierEtudiant&idEtud='.htmlspecialchars($_GET['idEtud']);
        $student = $this->modeleEtud->get_studentBD(htmlspecialchars($_GET['idEtud']));
        $idEtud = $student['idEtud'];
        $numApo = $student['numApogee'];
        $photoEtud = $student['photoEtud'];
        $nomEtud = $student['nomEtud'];
        $prenom = $student['prenomEtud'];
        $dateNaiss = $student['dateNaiss'];
        $courriel = $student['courriel'];
        $tel = $student['telEtud'];
        $adrr1 = $student['adr1'];
        $adrr2 = $student['adr2'];
        $AnneePromo = $student['anneePromotion'];
        $situationActu = $student['situationActuelle'];

        $etablissements = $this->contEtab->get_etablissementEtud(htmlspecialchars($_GET['idEtud']));
        
        if($photoEtud == "") $photoEtud = 'bootstrap-icons/icons/person.svg';
        if($dateNaiss == '0000-00-00') $dateNaiss = "";
        if($numApo == 0) $numApo = "";
        if($tel == 0) $tel = "" ;
        
        $groupes = $this->modeleEtud->get_groupes();
        
        require('modules/mod_etudiant/vue_etudiant/formEtud.php');
        echo '
        <script src="modules/mod_etudiant/script/adresse.js" ></script>';
        
    }

    function update_Etud(){
        if(empty(htmlspecialchars($_POST['nom'])) || empty(htmlspecialchars($_POST['prenom']))){
            throw new formModifEtudException("Impossible de modifier l'étudiant. Le nom et le prénom ne sont pas définie");
        }

        if($this->modeleEtud->studentModif_existBD($_POST['nom'],$_POST['prenom'],$_GET['idEtud']) != 0)
            throw new formModifEtudException('Impossible de modifier l\'étudiant. Le nom et le prénom ont déjà été attribué à un étudiant. ['.htmlspecialchars($_POST['nom']).' : '.htmlspecialchars($_POST['prenom']).']');

         if(!isset($_GET['idEtud'])) {
            throw new formModifEtudException('identifiant inconnu');
        }
      
        $photoEtud = self::fichier_image($_FILES);
        $this->modeleEtud->update_studentBD(htmlspecialchars($_POST['numApogee']),$photoEtud,htmlspecialchars($_POST['nom']),htmlspecialchars($_POST['prenom']),htmlspecialchars($_POST['dateNaiss']),
        htmlspecialchars($_POST['courriel']),htmlspecialchars($_POST['tel']),htmlspecialchars($_POST['adr1']),htmlspecialchars($_POST['adr2']),htmlspecialchars($_POST['anneePromotion']),htmlspecialchars($_POST['situationActuelle']),
        htmlspecialchars($_GET['idEtud']));

        //on modifi les etablissement qui existait déjà chez l'étudiant
        $this->contEtab->update_old_etablissement();
        $this->modeleEtud->add_etud_groupe($_GET['idEtud'],$_POST['selectGroupe']);
        //on rajoute des etablissement à l'étudiant si il y en a des nouveaux

        $this->contEtab->add_etablissementEtud(htmlspecialchars($_GET['idEtud']));

        //on réaffiche la liste des étudiants ou le formulaire en focntion de ce qui a été coché
        self::back_toPage();

        $message = 'ETUDIANT MODIFIE';
        require('modules/mod_etudiant/vue_etudiant/notification.php');
        echo '<script src="modules/mod_etudiant/script/adresse.js" ></script>';
    }

    public function delete_EtabDeEtudiant(){
        $this->contEtab->supp_EtabDeEtudiantBD(htmlspecialchars($_GET['idEtud']),htmlspecialchars($_GET['idEtab']));
        self::form_updateEtud();
        $message = 'SUPPRIMER';
        require('modules/mod_etudiant/vue_etudiant/notification.php');
        echo '<script src="modules/mod_etudiant/script/adresse.js" ></script>';
    }
    
    function delete_student($id){
        $this->modeleEtud->delete_studentBD(htmlspecialchars($_GET['idEtud']),$id);
        self::list_Student($id);
        $message = 'ETUDIANT supprime';
        require('modules/mod_etudiant/vue_etudiant/notification.php');
        echo '<script src="modules/mod_etudiant/script/adresse.js" ></script>';
    }
    
    public function importer_fichier(){

        require('modules/mod_etudiant/vue_etudiant/importer_fichier.php');
       
    }

public function mise_en_forme_du_message ($message,$nom,$prenom){

    $message=str_replace("[nom]", $nom, $message);
    $message=str_replace("[Nom]", $nom, $message);
    $message=str_replace("[NOM]", $nom, $message);
    $message=str_replace("[Prenom]", $prenom, $message);
    $message=str_replace("[PRENOM]", $prenom, $message);
    $message=str_replace("[prénom]", $prenom, $message);
    $message=str_replace("[prenom]", $prenom, $message);

    return $message;
}
public function validation_mail(){
    $list=$_SESSION['list'];
    unset($_SESSION['list']);

    if(isset($_POST['message']) && isset($_POST['sujet'])){
         /*for($i=0;$i<count($list);$i++){
            var_dump($this->modeleEtud->est_present($list[$i]['NuméroétudiantApogée']));
            $message=self::mise_en_forme_du_message($_POST['message'],$list[$i]['Nom'],$list[$i]['Prénom']);
            if(!empty($this->modeleEtud->est_present($list[$i]['NuméroétudiantApogée']))  || !mail($list[$i]['Courrielpersonnel'],$objet,$message)){
                $lesErreurs[$compteur]=$list[$i];
                $compteur+=1;
                //var_dump($lesErreurs);
            }else{
               $this->modeleEtud->add_studentBD_sans_img($list[$i]['NuméroétudiantApogée'],$list[$i]['Nom'],$list[$i]['Prénom'],$list[$i]['Datenaiss'],$list[$i]['Courrielpersonnel'],$list[$i]['telephoneannuel'],$list[$i]['adr1annuel'],$list[$i]['adr2annuel'],$list[$i]['Formation-annéepromotion'],$list[$i]['Situationactuelle']);
         */ 
        $compteur=0;
        $mailServeurExist;
        $objet=htmlspecialchars($_POST['sujet']);
        $message=htmlspecialchars($_POST['message']); 
        for($i=0;$i<count($list);$i++){
            $message=self::mise_en_forme_du_message($message,$list[$i]['nom'],$list[$i]['prenom']);
            if($this->modeleEtud->est_present($list[$i]['numeroetudiantapogee'])){
                //
                $tab[$compteur]=$list[$i];
                $compteur++;
            }else{
                                 //   mail($list[$i]['courrielpersonnel'],$objet,$message);  
                if(!isset($mailServeurExist)){
                    if(!@ mail($list[$i]['courrielpersonnel'],$objet,$message)){
                        $mailServeurExist=false;
                    }else{
                        $mailServeurExist=true;
                    }           
                }
                 $this->modeleEtud->add_studentBD_sans_img($list[$i]['numeroetudiantapogee'],$list[$i]['nom'],$list[$i]['prenom'],$list[$i]['datenaiss'],$list[$i]['courrielpersonnel'],$list[$i]['telephoneannuel'],$list[$i]['adr1annuel'],$list[$i]['adr2annuel'],$list[$i]['formation-anneepromotion'],$list[$i]['situationactuelle']);
                
            }


        }
        if(!$mailServeurExist) {
            $message = "Votre serveur Web ne permet pas l'envoi de mail, il peut etre installé";
            require('modules/mod_erreur/vue_erreur/popUp.php');
        }
        




        if($compteur>0){
            $h2='L\'insertion n\'a pas pu être effectuée car les élèves suivants sont déjà preésents :';
            require('modules/mod_etudiant/vue_etudiant/affichage_importation.php');
            echo "</article>";
            echo '</br><a href="index.php?module=etudiant&action=importer_fichier"><button>retour a l\'acceuil</button></a>';
            $content = ob_get_clean();
            require('template.php'); 

        }else{
            require('modules/mod_etudiant/vue_etudiant/confirmation_mail.php');
        }
        
        
    }
   
    
}
public function verification_des_attributs($attr){
    $attribut = self::list_attributs();
     $faux = array();
    $compteur=0;
    if(!empty($attr)){   
        for($i=0;$i<count($attr[0]);$i++){
              $nom=str_replace(' ', '',strtolower($attr[0][$i]));

                if(!in_array($nom,$attribut)){

                    $faux[$compteur]=$attr[0][$i];
                    $compteur++;

            }
        }
    }

    return $faux;
}


public function list_attributs(){
    return array (str_replace(' ', '',strtolower('formation-anneepromotion')),str_replace(' ', '',strtolower('numeroetudiantApogee')),str_replace(' ', '',strtolower('prenom')),str_replace(' ', '',strtolower('nom')),str_replace(' ', '',strtolower('datenaiss')),str_replace(' ', '',strtolower('courrielpersonnel')),str_replace(' ', '',strtolower('telephoneannuel')),str_replace(' ', '',strtolower('adr1annuel')),str_replace(' ', '',strtolower('adr2annuel')),str_replace(' ', '',strtolower('Poursuited\'etudes')),str_replace(' ', '',strtolower('situationactuelle')));
}

public function premiere_ligne_correct($line){
    $attribut=self::list_attributs();
    for($i=0;$i<count($line[0]);$i++){
        if(in_array(str_replace(' ', '',strtolower($line[0][$i])),$attribut)){
            return true;
        }
    }
    return false;
}



public function validation_fichier(){
     if(isset($_FILES)){

            $nomfichier = $_FILES['fileToUpload']['name']; 
            $extension = strrchr($nomfichier, "."); 
            $tmp_fich = $_FILES['fileToUpload']['tmp_name'];
            $extensions_autorisees = array('.csv');
            if ($_FILES['fileToUpload']['error'] == 0) {
                if(in_array($extension, $extensions_autorisees)){
                    $line=self::lecture_fichier($tmp_fich);
                    if(self::premiere_ligne_correct($line)){
                        $tab=self::convertisseur_tableau_en_array_par_ligne($line);
                           $attribut_faux=self::verification_des_attributs($line);
                        if(empty($attribut_faux)){
                            $_SESSION['list']=$tab;
                            $h2='Voici votre importation';
                            require('modules/mod_etudiant/vue_etudiant/affichage_importation.php');
                            require('modules/mod_etudiant/vue_etudiant/form_mail.php');
                        }else{
                           // $etat=1;
                           // require('mod_etudiant/vue_etudiant/erreur_fichier.php');
                             for($i=0;$i<count($attribut_faux);$i++){
                               $attributs=$attributs."</br> -".$attribut_faux[$i];
                            }
                            throw new formImportException("Les attributs suivants sont mal entrés<strong>(faites attention au espaces au debut et a la fin des attributs)</strong>  ou n'existe pas :".$attributs);
                            
                        }
     
                     }else{
                        //$etat=2;
                       // require('mod_etudiant/vue_etudiant/erreur_fichier.php');
                         throw new formImportException("votre fichier ne commence pas par la case A1");
                     }
                        
                     
                 
                } else{
                  //  $etat=3;
                  //  require('mod_etudiant/vue_etudiant/erreur_fichier.php');
         throw new formImportException("le format de votre fichier doit etre de type .csv et non ".$extension);
                   
                }   
            }else{
                // $etat=4;
               //require('mod_etudiant/vue_etudiant/erreur_fichier.php');
                 throw new formImportException("erreur d'importation");
            }

    }
}


public function lecture_fichier($fichier){
     $file = fopen($fichier, 'r');
     $premiere_ligne=fgets($file);
     if(substr_count($premiere_ligne, ';') > substr_count($premiere_ligne, ',') ){
        $caractere=';';
     }else{
        $caractere=',';
     }
     fclose($file);
     $file = fopen($fichier, 'r');
     while (!feof($file) ) {
        $line[] = fgetcsv($file, 1024,$caractere);
     }
    return $line;

}

public function convertisseur_tableau_en_array_par_ligne($tab){
$taille=count($tab);
$taille1=count($tab[0]);
     $array1=array();
        for($i=1;$i<$taille;$i++){
            for($j=0;$j<$taille1;$j++){
                $nomTab=trim(str_replace(' ', '',strtolower($tab[0][$j]))," \t\n\r\0\x0B".chr(0xC2).chr(0xA0));
                $a[$nomTab]=$tab[$i][$j];
            }
            if (!empty($a)) {
                array_push($array1,$a);
            }
        }
    return $array1;
}


public function creer_groupe(){
    if (isset($_POST['nomGroupe']) and !empty($_POST['nomGroupe'])){
        $nomExists = $this->modeleEtud->groupe_exists(htmlspecialchars($_POST['nomGroupe']));
        if ($nomExists == 0) {
            $this->modeleEtud->creer_groupe($_POST['nomGroupe']);
            self::liste_groupe_etud();
        }
        else {
           throw new formGroupeException('Le nom de groupe est déjà existant');
        }
    }
}
    public function liste_groupe_etud(){
        $listeGroupe = $this->modeleEtud->get_groupes();
        $mod=$this->modeleEtud;
        require('modules/mod_etudiant/vue_etudiant/groupeEtude.php');
    }
    
    public function supprimer_groupe($idGroupe) {
        $this->modeleEtud->supprimer_groupe($idGroupe);
        self::liste_groupe_etud();
    }
    
 
}