<?php
//
session_start();

require_once 'connectionDB.php';
require_once 'creerReseauFictive.php';
//set_time_limit(360);
connectToDB();
videTables();

$user = $_GET["user"];
$_SESSION['user'] = $user;

$wikiUrl = "http://" . $_GET["wiki"];
$_SESSION['wikiUrl'] = $wikiUrl;

$firstQueryUrl = $wikiUrl 
	. "/w/api.php?action=query&list=usercontribs&format=json&uclimit=500&ucuser=" 
	. $user . "&ucnamespace=1&ucprop=title|ids";
$firstQueryContent = getQueryContent($firstQueryUrl);

$objTalk = json_decode($firstQueryContent, true);
$queryTalk = $objTalk['query'];
$userTalks = $queryTalk['usercontribs']; 
insertTalks($userTalks);
$talks = getTalks($user);
if (!$talks) {
    $message  = "Invalid query: " . mysql_error() . "\n";
    die($message);
}
printUserTalks($talks, $user);

function getTalks($user) {		  
    $sql = "SELECT discussionId, titre FROM discussion;";
    return mysql_query($sql);
}

function insertTalks($userTalks) { //permet d'inserer dans la table discussion la liste de toutes les discussions auxquelles le user a participe
	
    $nbTalks = sizeof($userTalks);
    for($i=0;$i<$nbTalks;$i++) {
        $talk = $userTalks[$i];
	$fixedTitle=($talk['title']);		
        $tab = explode(':',$fixedTitle,2);		 
        $titreDiscussion = $tab[1] ;
         
	$query = "INSERT INTO grisou.discussion (discussionId, titre) VALUES (".$talk['pageid'].", '".$titreDiscussion ."')" ;		
	Mysql_Query($query);
		
	$query = "INSERT INTO grisou.user (userId, userName) VALUES (".$talk['userid'].", '".$talk['user'] ."')";
	Mysql_Query($query);
        $zero=0;
        
        $userId = $talk['userid'];
        $query = "insert into grisou.intervenants(intervenantId,intervenantName,intervenantAuteurArticle) values(".$userId.", '".$talk['user'] ."',".$zero.");";
        Mysql_Query($query);
    }
     
}

function printUserTalks($talks, $user) {
    $listeDiscussion = "listeDiscussion";
	
    $result = "<h2>Discussions auxquelles <span style='color:blue;'>" . $user . "</span> a contribu&eacute;</h2>
    <p style='color:blue;'>Cliquez sur une ligne pour choisir une discussion.<p>
    <table class='tbl_result' width='100%' >
    <tr>            
    <td class='head' width='20%'>Page ID</td>
    <td class='head' width='60%'>Titre de la discussion</td>  
    <td class='head' width='20%' style='text-align:left;'>Cr&eacute;ateur</td>
    </tr></table><div id=$listeDiscussion><table class='tbl_result' width='100%'>";	
		
    if (!$talks) {
        echo "Impossible d'ex�cuter la requ�te dans la base : " . mysql_error();
        exit;
    }
    if (mysql_num_rows($talks) == 0) {
        echo "Aucune ligne trouv�e, rien � afficher.";
        exit;
    }
    
    while($row = mysql_fetch_assoc($talks)) {
        $envoi = '"'."envoiDiscussion(this)".'"';
    	$pageId = $row["discussionId"];
        $pageTitle = $row["titre"];		
        $isTalkCreator = " ";
			
	$result .= "<tr class='res' onclick = $envoi>";               
	$result .= "<td class='res' width='20%' >" . $pageId . "</td>";
	$result .= "<td class='res' width='60%' >" . $pageTitle . "</td>";		
	$result .= "<td class='res' width='20%' style='text-align:left;'>" . $isTalkCreator . "</td>";
	$result .= "</tr>";	
    }
  
    $result .= "</table></div>";
    print $result;		
}

function videTables() {
    
    // on vide les tables � chaque nouvelle connection
    mysql_query('TRUNCATE TABLE discussion;');
    mysql_query('TRUNCATE TABLE user;');
    mysql_query('TRUNCATE TABLE intervenants;');
    mysql_query('TRUNCATE TABLE liens;');
    mysql_query('TRUNCATE TABLE centralites;');
    
        
}

function getQueryContent($queryUrl) {
    return file_get_contents($queryUrl, true);
}

?>