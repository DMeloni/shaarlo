<?php
/*
ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);*/
// Retour une objet mysqli
require_once('fct/fct_session.php');

function shaarliMyConnect() {
    global $MYSQL_USER, $MYSQL_SERVER, $MYSQL_PASSWORD, $MYSQL_DB;
    
    $mysqli = new mysqli($MYSQL_SERVER, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB);

    /* check connection */
    if ($mysqli->connect_errno) {
        return null;
    }
    
    $mysqli->set_charset("utf8");
    
    return $mysqli;
}

function insertArticles($mysqli, $articles) {
    if($mysqli === null) {
        return null;
    }
    
    insertEntites($mysqli, 'liens', $articles);
}

function insertEntite($mysqli, $table, $entite) {
    return insertEntites($mysqli, $table, array($entite));
}

function insertEntites($mysqli, $table, $entites) {
    if($mysqli === null) {
        return null;
    }
    if(!is_array($entites)) {
        return null;
    }
    $premierArticle = reset($entites);
    
    if(!is_array($premierArticle)) {
        return null;
    }
    $clefsSQL = array_keys($premierArticle);
    
    $sql = array(); 
    
    foreach( $entites as $row ) {
        $valeurs = array();
        foreach ($clefsSQL as $colonneSQL ) {
            $valeurs[] = sprintf('"%s"',  $mysqli->real_escape_string($row[$colonneSQL]));
        }
        $sql[] = sprintf('(%s)', implode(', ', $valeurs));
    }
    
    //var_dump($sql);

    $requeteClefSQL = implode(', ', $clefsSQL);
    
    if($table == 'liens') {
        $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE id_commun=VALUES(id_commun), date_update=VALUES(date_update), url_simplifiee=VALUES(url_simplifiee), article_description=VALUES(article_description), id_rss_origin=VALUES(id_rss_origin), id_rss=VALUES(id_rss), tags=VALUES(tags) ', $table, $requeteClefSQL, implode(',', $sql));
    }elseif($table == 'rss') {
        $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE date_update=VALUES(date_update), rss_titre=VALUES(rss_titre)', $table, $requeteClefSQL, implode(',', $sql));
    }elseif($table == 'shaarliste') {
        $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE pseudo=VALUES(pseudo),date_update=VALUES(date_update),url=VALUES(url)', $table, $requeteClefSQL, implode(',', $sql));
    }elseif($table == 'liens_clic') {
        $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE nb_clic=nb_clic+1,date_update=VALUES(date_update)', $table, $requeteClefSQL, implode(',', $sql)); 
    }else {
        $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE date_update=VALUES(date_update)', $table, $requeteClefSQL, implode(',', $sql));
    }

    return $mysqli->query($requeteSQL);
}


function shaarliMyDisconnect($mysqli) {
    $mysqli->close();
}

function getIdCommunFromShaarliLink($mysqli, $uuid) {
    $uuidSansHttps = str_replace('https://', 'http://', $uuid);
    $uuidAvecHttps = str_replace('http://', 'https://', $uuidSansHttps);
    
    $query = sprintf("SELECT id_commun, article_url FROM `liens` WHERE `article_uuid`='%s' OR `article_uuid`='%s'", $mysqli->real_escape_string($uuidAvecHttps), $mysqli->real_escape_string($uuidSansHttps));
    if ($result = $mysqli->query($query)) {
        if ($row = $result->fetch_assoc()) {
            return $row;
        }
    }
    
    return null;
}

function getIdRssOriginFromShaarliLink($mysqli, $link) {

    $link = explode('?', $link);
    $link = $link[0];
    $linkSimplifie = simplifieUrl($link);
    $linkSansHttps = md5($linkSimplifie);
    $linkAvecSlash = md5($linkSimplifie . '/' );
    
    $query = sprintf("SELECT id FROM `rss` WHERE `id`='%s' OR `id` = '%s' OR `url_simplifiee` LIKE '%s%s'", $linkAvecSlash, $linkSansHttps, $mysqli->real_escape_string($linkSimplifie), '%');
   if ($result = $mysqli->query($query)) {
        if ($row = $result->fetch_assoc()) {
            return $row['id'];
        }
    }
    
    return null;
}

function creerArticle($id, $idCommun, $articleUrl, $urlSimplifie, $articleTitle, $articleDescription, $spam, $articleDate, $articleUuid, $idRss, $idRssOrigin, $category = '') {
    
    $article = array('id' => $id
                ,'id_commun' => $idCommun
                ,'article_url' => $articleUrl
                ,'url_simplifiee' => $urlSimplifie
                ,'article_titre' => $articleTitle
                ,'article_description' => $articleDescription
                ,'spam'          => $spam
                ,'article_date'  => $articleDate
                ,'article_uuid'  => $articleUuid
                ,'id_rss'        => $idRss
                ,'id_rss_origin' => $idRssOrigin
                ,'tags'          => $category
            );
            
    $article['date_update'] = date('YmdHis');
    
    $article['date_insert'] = $article['date_update'];
    
    return $article;
}

function creerTag($idLien, $tag) {
    $article = array('id_lien' => $idLien,'nom' => $tag);
    $article['date_update'] = date('YmdHis');

    return $article;
}

function creerRss($id, $titre, $url, $urlSimplifiee, $active = 0, $urlFavicon='') {
    
    $entite = array('id' => $id
                ,'rss_titre' => $titre
                ,'url' => $url
                ,'active' => $active
                ,'url_simplifiee' => $urlSimplifiee
                ,'url_favicon' => $urlFavicon
            );
            
    $entite['date_update'] = date('YmdHis');
    
    $entite['date_insert'] = $entite['date_update'];

    
    return $entite;
}

function creerMonRss($username, $idRss, $pseudo, $alias = '') {
    
    $entite = array('username' => $username
                ,'id_rss' => $idRss
                ,'pseudo' => $pseudo
                ,'alias' => $alias
            );
            
    $entite['date_update'] = date('YmdHis');
    
    $entite['date_insert'] = $entite['date_update'];
    
    return $entite;
}


function creerShaarliste($username, $pseudo, $url) {
    
    $entite = array('username' => $username
                ,'pseudo' => $pseudo
                ,'url' => $url
            );

    $entite['date_update'] = date('YmdHis');
    $entite['date_insert'] = $entite['date_update'];
    
    return $entite;
}

function creerLiensClic($idCommun) {
    $entite = array('id_commun' => $idCommun);

    $entite['nb_clic'] = 1;
    $entite['date_update'] = date('YmdHis');
    $entite['date_insert'] = $entite['date_update'];
    
    return $entite;
}

function creerShaarlieurLiensClic($idCommun, $idShaarlieur) {
    $entite = array('id_commun' => $idCommun, 'id_shaarlieur' => $idShaarlieur);

    $entite['date_update'] = date('YmdHis');
    $entite['date_insert'] = $entite['date_update'];
    
    return $entite;
}

function getMeilleursArticlesDuJour($mysqli, $dateTimeFrom, $dateTimeTo, $limit=1, $id=null) {
    if(is_null($id)) {
        $query = sprintf('SELECT * 
            FROM liens 
            JOIN (SELECT `id_commun` 
                    FROM `liens_clic` 
                    WHERE `date_insert`>="%s" AND `date_insert`<="%s"  
                    ORDER BY `nb_clic` DESC LIMIT %s
                  ) AS id_meilleur_lien 
            ON liens.id_commun = id_meilleur_lien.id_commun 
            JOIN rss ON rss.id = liens.id_rss 
            ORDER BY liens.date_insert ASC limit 1'
            , $dateTimeFrom->format('YmdH0000')
            , $dateTimeTo->format('YmdH5959')
            , $limit
            );
    }else {
        $query = sprintf('SELECT * 
            FROM liens 
            JOIN rss ON rss.id = liens.id_rss 
            WHERE liens.id_commun="%s"
            ORDER BY liens.date_insert ASC limit 1'
            , $id
            );
    }
    $articles = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $articles[$row['id_commun']] = $row;
        }
    }

    return $articles;
}


function getAllArticlesDuJour($mysqli, $username=null, $fullText = null, $popularite=0, $orderBy = null, $order='desc', $from=null, $to=null, $limit=null, $tags=null) {
    $articles = array();
    $matchSQL ='';
    if(!empty($fullText)) {
        $fullText = $mysqli->real_escape_string(urldecode($fullText));
        
        // Recherche d'un id commun en particulier
        $matches = array();
        if (preg_match_all('#^id:([0-9a-f]{32})$#', $fullText, $matches) === 1) {
            $fullText = $matches[1][0]; 
            $matchSQL = " AND l.id_commun = '" . $fullText . "'";
            $limit=null;
            $from=null;
            $to=null; 
        }elseif (preg_match_all('#^shaarli:([0-9a-f]{32})$#', $fullText, $matches) === 1) {
            //Recherche d'un shaarliste en particulier
            $fullText = $matches[1][0]; 
            $matchSQL = " AND l.id_rss = '" . $fullText . "'";
            $limit=null;
            $from=null;
            $to=null; 
        }
        // Recherche d'un lien en particulier
        elseif (strpos($fullText, 'http') === 0) {
            $matchSQL = " AND l.article_url = '" . $fullText . "'";
        } else {
        // Recherche fulltext
            $fullText = str_replace('%', '', $fullText);
            $matchSQL = " AND l.article_url != 'http://' AND (MATCH (l.article_titre,l.article_description) AGAINST ('$fullText') OR l.article_titre LIKE  '%%" . $fullText . "%%' OR l.article_uuid LIKE '%%" . $fullText . "%%') ";
        }
    }
    
    if(isset($_GET['simulate'])) {
        echo $matchSQL;
    }    
    $betweenDateSQL ='';
    if(!is_null($from) && !is_null($to)) {
        $betweenDateSQL = sprintf(" AND l.article_date BETWEEN '%s' AND '%s' ",$mysqli->real_escape_string($from),$mysqli->real_escape_string($to));
    }
    if(is_null($from) && !is_null($to)) {
        $betweenDateSQL = sprintf(" AND l.article_date <= '%s' ",$mysqli->real_escape_string($to));
    }
    if(!is_null($from) && is_null($to)) {
        $betweenDateSQL = sprintf(" AND l.article_date >= '%s' ",$mysqli->real_escape_string($from));
    }   
    $matchSQL .= $betweenDateSQL;
    
    $havingSQL = sprintf(" having count(*) >= %s", (int)$mysqli->real_escape_string($popularite));
    
    $orderByPopularity = '';

    $orderSQL = ' DESC';
    if($order == SORT_ASC){
        $orderSQL = ' ASC';
    }
    
    $limitSQL=' limit 1000 ';
    if(!is_null($limit)) {
        $limitSQL = sprintf(" limit %s ",$mysqli->real_escape_string($limit));
    } 
        
    $orderBySQL = '';
    $limitSQLDate = '';
     if($orderBy == 'pop'){
        //$orderBySQL = 'ORDER BY c ';
        $orderByPopularity = 'ORDER BY c ';
        $orderByPopularity .= $orderSQL;
    }
    elseif($orderBy == 'rand'){
        //$orderBySQL = 'ORDER BY c ';
        $limitSQL=' limit 50 ';
        $orderByPopularity = 'ORDER BY RAND() ';
        $orderByPopularity .= $orderSQL;
    }
    else {
        $limitSQLDate = $limitSQL;
        $orderBySQL = ' ORDER BY l.article_date ';
        $orderBySQL .= $orderSQL;
    }
    
    $username = $mysqli->real_escape_string($username);
    
    
    $jointureTags = 'WHERE';
    
    // Ajout de la contrainte sur les tags 
    if (!empty($tags)) {
        $tagsIN = arrayToIN($mysqli, $tags);
        $jointureTags = " JOIN tags ON l.id=tags.id_lien WHERE tags.nom $tagsIN AND ";
    }
    
    if(!is_null($username)) {
        $query = sprintf("SELECT liens.*, rss.rss_titre, mes_rss.alias, rss_origin.rss_titre AS rss_titre_origin, rss_origin.url AS rss_url_origin, mes_rss_origin.alias AS alias_origin from liens 
        INNER JOIN (
            SELECT liens.id_commun, count(*) as c from liens INNER JOIN (
                SELECT id_commun, count(*) as c FROM `liens` as l $jointureTags l.id_rss IN (
                    SELECT id_rss from mes_rss AS m 
                    JOIN rss ON m.id_rss = rss.id 
                    WHERE m.username='$username' AND rss.active = '1'
                ) AND l.active = '1' AND l.id_commun != 'd41d8cd98f00b204e9800998ecf8427e' $matchSQL GROUP BY l.id_commun $orderBySQL $limitSQLDate
            ) AS jour ON jour.id_commun=liens.id_commun WHERE liens.id_rss IN (
                    SELECT id_rss from mes_rss AS m 
                    JOIN rss ON m.id_rss = rss.id 
                    WHERE m.username='$username' AND rss.active = '1'
                ) group by liens.id_commun $havingSQL $orderByPopularity $limitSQL
        ) AS articles_du_jour ON liens.id_commun=articles_du_jour.id_commun 
        INNER JOIN rss ON liens.id_rss=rss.id 
        LEFT OUTER JOIN rss AS rss_origin ON liens.id_rss_origin=rss_origin.id 
        LEFT OUTER JOIN mes_rss AS mes_rss_origin ON (rss_origin.id=mes_rss_origin.id_rss AND mes_rss_origin.username='$username')

        LEFT OUTER JOIN mes_rss ON (rss.id=mes_rss.id_rss AND mes_rss.username='$username')
        /*WHERE liens.id_rss IN (
                SELECT id_rss from mes_rss AS m WHERE m.username='$username'
        ) */
        WHERE rss.active = '1'
        GROUP BY liens.id 
        ORDER BY liens.article_date DESC");
    }
    else{
        $query = sprintf("SELECT * from liens INNER JOIN (SELECT id_commun, count(*) as c FROM `liens` AS l WHERE l.id_rss IN (SELECT id from rss) %s GROUP BY l.id_commun %s %s %s) AS articles_du_jour ON liens.id_commun=articles_du_jour.id_commun INNER JOIN rss ON liens.id_rss=rss.id GROUP BY liens.id ORDER BY liens.article_date DESC", $matchSQL, $havingSQL,$orderBySQL, $limitSQL);
    }
    if(isset($_GET['simulate'])) {
        echo $query;
    }
    //
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $articles[] = $row;;
        }
    }

    return $articles;
}

function getAllAbonnementsId($mysqli, $username) {
    $entites = array();

    $query = sprintf("SELECT id_rss from mes_rss WHERE mes_rss.username='%s'", $mysqli->real_escape_string($username));

    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $entites[] = $row['id_rss'];
        }
    }

    return $entites;
}

function arrayToIN($mysqli, $array) {
    $arrayEscaped = array();
    foreach ($array as $v) {
        $arrayEscaped[] = "'" . $mysqli->real_escape_string($v) . "'";
    }

    return sprintf(' IN (%s)', implode(',', $arrayEscaped));
}

function getAllAbonnements($mysqli, $username) {
    $entites = array();

    $query = sprintf("SELECT * from mes_rss INNER JOIN rss ON mes_rss.id_rss=rss.id WHERE mes_rss.username='%s'", $mysqli->real_escape_string($username));

    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $entites[$row['id_rss']] = $row;
        }
    }

    return $entites;
}

function getAllRssActifs($mysqli) {
    $entites = array();

    $query = sprintf("SELECT url FROM `rss` WHERE active=1");

    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $entites[] = $row['url'];
        }
    }

    return $entites;
}

/**
 * Indique si un id existe ou pas
 */
function idRssExists($mysqli, $urlSimplifiee) {
    $entites = array();

    $query = sprintf("SELECT id FROM `rss` WHERE url_simplifiee='%s'", $mysqli->real_escape_string($urlSimplifiee));
    
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            return $row['id'];
        }
    }

    return null;
}


/**
 * Indique si un id existe ou pas
 */
function getShaarliste($mysqli, $username) {
    $entites = array();

    $query = sprintf("SELECT * FROM `shaarliste` WHERE username='%s'", $mysqli->real_escape_string($username));
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            return $row;
        }
    }

    return null;
}
/**
 * Retourne le titre d'un shaarli en fonction de son id
 */
function getRssTitleFromId($mysqli, $idRss) {
    $entites = array();

    $query = sprintf("SELECT rss_titre FROM `rss` WHERE id='%s'", $mysqli->real_escape_string($idRss));
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            return $row['rss_titre'];
        }
    }

    return null;
}

function deleteRss($mysqli, $username, $rssId) {
    $query = sprintf("DELETE FROM mes_rss WHERE username='%s' AND id_rss='%s'", $mysqli->real_escape_string($username), $mysqli->real_escape_string($rssId));
    $mysqli->query($query);
}

// Supprime tous les abonnements d'un utilisateur
function deleteMesRss($mysqli, $username) {
    $query = sprintf("DELETE FROM mes_rss WHERE username='%s'", $mysqli->real_escape_string($username));
    $mysqli->query($query);
}

function selectAllShaarlistes($mysqli, $onlyActive = true){

    $condActive = ' AND r.active = 1';
    if(!$onlyActive) {
        $condActive = '';
    }
    $query = sprintf("SELECT r.id, r.rss_titre as title, 
    r.url as link,
    r.date_insert as createdateiso,
    r.date_update as pubdateiso,
    r.active,
    count(*) AS nb_items 
    FROM `liens` as l 
    LEFT JOIN rss as r ON l.id_rss = r.id where r.id IS NOT NULL $condActive 
    group by id_rss
    ORDER BY r.date_insert DESC, r.date_update DESC
    ");
    $query = sprintf("SELECT r.id, r.rss_titre as title, 
    r.url as link,
    r.`404` as is_dead,
    r.date_insert as createdateiso,
    r.date_update as pubdateiso,
    r.active,
    count(*) AS nb_items 
    FROM `rss` as r
    LEFT JOIN liens as l ON l.id_rss = r.id 
    where r.id IS NOT NULL $condActive 
    group by id_rss
    ORDER BY r.date_insert DESC, r.date_update DESC
    ");

    $results = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $results[$row['id']] =  $row;
        }
    }

    return $results;
}


function selectAllShaarlistesId($mysqli){
    $query = sprintf("SELECT id  from rss");

    $results = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $results[$row['id']] =  $row['id'];
        }
    }

    return $results;
}

function bloquerRss($mysqli, $rssId) {
    $query = sprintf("UPDATE rss SET active=2 WHERE id='%s'", $mysqli->real_escape_string($rssId));
    echo $query;
    $mysqli->query($query);
}

function validerRss($mysqli, $rssId) {
    $query = sprintf("UPDATE rss SET active=1 WHERE id='%s'", $mysqli->real_escape_string($rssId));
    $mysqli->query($query);
}

function bloquerLien($mysqli, $rssId) {
    $query = sprintf("UPDATE liens SET active=2 WHERE id='%s'", $mysqli->real_escape_string($rssId));
    echo $query;
    $mysqli->query($query);
}

function validerLien($mysqli, $rssId) {
    $query = sprintf("UPDATE liens SET active=1 WHERE id='%s'", $mysqli->real_escape_string($rssId));
    $mysqli->query($query);
}



function selectShaarlieur($mysqli, $shaarlieurId){
    $query = sprintf("SELECT shaarlieur.*, rss.id AS id_rss FROM `shaarlieur` 
         LEFT OUTER JOIN rss ON rss.url=shaarlieur.shaarli_url
         WHERE shaarlieur.id='%s'
    ", $mysqli->real_escape_string($shaarlieurId));

    $results = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            return $row;
        }
    }

    return null;
}

function selectShaarlieursWithInscriptionAuto($mysqli){
    $query = sprintf("SELECT id FROM `shaarlieur`  where inscription_auto='1'");

    $results = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $results[$row['id']] =  $row['id'];
        }
    }

    return $results;
}

function selectShaarlieursWithShaarliPublic($mysqli, $shaarliOk){
    $query = sprintf("SELECT * FROM `shaarlieur` where shaarli_private='0' AND shaarli_ok='%s' AND shaarli_url != ''  ORDER BY id", $mysqli->real_escape_string($shaarliOk));

    $results = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $results[md5($row['id'])] =  $row;
        }
    }

    return $results;
}

function creerShaarlieur($shaarlieurId, $pwd, $data) {
    
    $entite = array('id' => $shaarlieurId
                ,'data' => $data
                ,'pwd' => $pwd
            );

    $entite['date_update'] = date('YmdHis');
    $entite['date_insert'] = $entite['date_update'];
    
    return $entite;
}

function updateShaarlieurData($mysqli, $shaarlieurId, $data) {
    $dateUpdate = date('YmdHis');
    $query = sprintf("UPDATE shaarlieur SET data='%s', date_update='%s' WHERE id='%s'", $mysqli->real_escape_string($data), $mysqli->real_escape_string($dateUpdate), $mysqli->real_escape_string($shaarlieurId));
    $mysqli->query($query);
}

function updateShaarlieurInscriptionAuto($mysqli, $shaarlieurId, $inscriptionAuto) {
    $dateUpdate = date('YmdHis');
    $query = sprintf("UPDATE shaarlieur SET inscription_auto='%s', date_update='%s' WHERE id='%s'", $mysqli->real_escape_string($inscriptionAuto), $mysqli->real_escape_string($dateUpdate), $mysqli->real_escape_string($shaarlieurId));
    $mysqli->query($query);
}

function updateShaarlieurPassword($mysqli, $shaarlieurId, $password) {
    $dateUpdate = date('YmdHis');
    $query = sprintf("UPDATE shaarlieur SET pwd='%s' WHERE id='%s'", $mysqli->real_escape_string($password), $mysqli->real_escape_string($shaarlieurId));
    $mysqli->query($query);
}



function majDerniereConnexion($mysqli, $shaarlieurId) {
    $dateDerniereConnexion = date('YmdHis');
    $query = sprintf("UPDATE shaarlieur SET nb_connexion=nb_connexion+1, date_derniere_connexion='%s' WHERE id='%s'", $mysqli->real_escape_string($dateDerniereConnexion), $mysqli->real_escape_string($shaarlieurId));
    $mysqli->query($query);
}

function updateShaarlieurShaarliUrl($mysqli, $shaarlieurId, $shaarliUrl, $shaarliPrivate) {
    $dateUpdate = date('YmdHis');
    $shaarliPrivateSql = '1';
    if (false === $shaarliPrivate) {
        $shaarliPrivateSql = '0';
    }
    $query = sprintf("UPDATE shaarlieur SET shaarli_url='%s', date_update='%s', shaarli_private='%s' WHERE id='%s'", 
        $mysqli->real_escape_string($shaarliUrl), 
        $mysqli->real_escape_string($dateUpdate), 
        $mysqli->real_escape_string($shaarliPrivateSql), 
        $mysqli->real_escape_string($shaarlieurId)
    );
    $mysqli->query($query);
}

function updateShaarlieurShaarliOk($mysqli, $shaarlieurId, $shaarliUrlIdOk, $shaarliUrlOk) {
    $dateUpdate = date('YmdHis');
    $query = sprintf("UPDATE shaarlieur SET shaarli_ok='1', date_update='%s', shaarli_url_id_ok='%s', shaarli_url_ok='%s' WHERE id='%s'", 
        $mysqli->real_escape_string($dateUpdate), 
        $mysqli->real_escape_string($shaarliUrlIdOk), 
        $mysqli->real_escape_string($shaarliUrlOk), 
        $mysqli->real_escape_string($shaarlieurId)
    );
    $mysqli->query($query);
}


function creerMessage($shaarlieurId, $message) {
    
    $entite = array('id_shaarlieur' => $shaarlieurId
                ,'message' => $message
            );

    $entite['date_update'] = date('YmdHis');
    $entite['date_insert'] = $entite['date_update'];
    
    return $entite;
}


function switchShaarliste($mysqli, $idAncien, $idNouveau) {
    // Switch des anciens liens vers le nouvel id
    $query = sprintf("UPDATE liens SET id_rss='%s' WHERE id_rss='%s'",
        $mysqli->real_escape_string($idNouveau), 
        $mysqli->real_escape_string($idAncien)
    );
    echo $query;
    $mysqli->query($query);
    
    // Desactivation de l'ancien shaarliste
    // Switch des anciens liens vers le nouvel id
    $query = sprintf("UPDATE rss SET active='0' WHERE id='%s'",
        $mysqli->real_escape_string($idAncien)
    );
    $mysqli->query($query);
    echo $query;
}

/**
 * Retourne le nombre de liens qu'un utilisateur a cliqué
 * 
 * @param $mysqli
 * @param string $shaarlieurId
 * 
 * @return int c : le nombre de lien
 */
function getNombreDeClicsFromShaarlieurId($mysqli, $shaarlieurId) {
    $query = sprintf("SELECT count(*) AS c FROM shaarlieur_liens_clic WHERE id_shaarlieur='%s'", $mysqli->real_escape_string($shaarlieurId));

    $results = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            return $row['c'];
        }
    }

    return 0;
}

/**
 * Retourne la position du shaarlieur dans le top des shaarlieur
 * 
 * @param $mysqli
 * @param string $shaarlieurId
 * 
 * @return array('row_number' => '10', 'c' => '80', 'id_shaarlieur' => 'stuper')
 */
function getTopShaarlieurFromShaarlieurId($mysqli, $shaarlieurId) {
    $query = sprintf("SELECT * from (SELECT @rownum:=@rownum + 1 as row_number, a.* from (select count(*) as c, `id_shaarlieur` FROM `shaarlieur_liens_clic` GROUP BY `id_shaarlieur` order by c desc) as a,(SELECT @rownum := 0) as r ) as j WHERE id_shaarlieur='%s'", $mysqli->real_escape_string($shaarlieurId));

    $results = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            return $row;
        }
    }

    return null;
}


/**
 * Retourne le top des tags éligibles aux badges
 * pour un utilisateur
 * 
 * @param $mysqli
 * @param string $shaarlieurId
 * @param array  $tags
 * 
 * @return array(array('nom' => 'shaarli', 'c' => '80')...)
 */
function getTopTagsFromShaarlieurIdAndTags($mysqli, $shaarlieurId, $tags) {
    if (empty($tags)) {
        return array();
    }
    $tagsIN = arrayToIN($mysqli, $tags);
    
    $query = sprintf("
        SELECT t.nom, count(*) as c 
        FROM `shaarlieur_liens_clic` as lc 
            LEFT JOIN liens AS l ON lc.id_commun=l.id_commun 
            LEFT JOIN tags AS t ON l.id=t.id_lien  
        WHERE t.nom IS NOT NULL AND lc.`id_shaarlieur`='%s'
        AND t.nom $tagsIN
        GROUP BY t.nom
        ORDER BY count(*) DESC
        "
        , $mysqli->real_escape_string($shaarlieurId)
    );

    $results = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $results[md5(strtolower($row['nom']))] = $row;
        }
    }

    return $results;
}






