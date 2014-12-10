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
        $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE id_commun=VALUES(id_commun), date_update=VALUES(date_update), url_simplifiee=VALUES(url_simplifiee), article_description=VALUES(article_description), id_rss_origin=VALUES(id_rss_origin), id_rss=VALUES(id_rss) ', $table, $requeteClefSQL, implode(',', $sql));
    }elseif($table == 'rss') {
        $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE date_update=VALUES(date_update), rss_titre=VALUES(rss_titre)', $table, $requeteClefSQL, implode(',', $sql));
    }elseif($table == 'shaarliste') {
        $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE pseudo=VALUES(pseudo),date_update=VALUES(date_update),url=VALUES(url)', $table, $requeteClefSQL, implode(',', $sql));
    }elseif($table == 'liens_clic') {
        $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE nb_clic=nb_clic+1,date_update=VALUES(date_update)', $table, $requeteClefSQL, implode(',', $sql)); 
    }else {
        $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE date_update=VALUES(date_update)', $table, $requeteClefSQL, implode(',', $sql));
    }

    $mysqli->query($requeteSQL);
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

function creerArticle($id, $idCommun, $articleUrl, $urlSimplifie, $articleTitle, $articleDescription, $spam, $articleDate, $articleUuid, $idRss, $idRssOrigin) {
    
    $article = array('id' => $id
                ,'id_commun' => $idCommun
                ,'article_url' => $articleUrl
                ,'url_simplifiee' => $urlSimplifie
                ,'article_titre' => $articleTitle
                ,'article_description' => $articleDescription
                ,'spam' => $spam
                ,'article_date' => $articleDate
                ,'article_uuid' => $articleUuid
                ,'id_rss'       => $idRss
                ,'id_rss_origin' => $idRssOrigin
            );
            
    $article['date_update'] = date('YmdHis');
    
    $article['date_insert'] = $article['date_update'];
    
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

function getMeilleursArticlesDuJour($mysqli, $dateTimeFrom, $dateTimeTo, $limit=1) {
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

    $articles = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $articles[$row['id_commun']] = $row;
        }
    }

    return $articles;
}


function getAllArticlesDuJour($mysqli, $username=null, $fullText = null, $popularite=0, $orderBy = null, $order='desc', $from=null, $to=null, $limit=null) {
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
            $matchSQL = " AND l.article_url != 'http://' AND (MATCH (l.article_titre,l.article_description) AGAINST ('$fullText') OR l.article_uuid LIKE '%%" . $fullText . "%%') ";
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
        $orderByPopularity = 'ORDER BY RAND() ';
        $orderByPopularity .= $orderSQL;
    }
    else {
        $limitSQLDate = $limitSQL;
        $orderBySQL = ' ORDER BY l.article_date ';
        $orderBySQL .= $orderSQL;
    }
    
    $username = $mysqli->real_escape_string($username);
    
    if(!is_null($username)) {
        $query = sprintf("SELECT liens.*, rss.rss_titre, mes_rss.alias, rss_origin.rss_titre AS rss_titre_origin, rss_origin.url AS rss_url_origin, mes_rss_origin.alias AS alias_origin from liens 
        INNER JOIN (
            SELECT liens.id_commun, count(*) as c from liens INNER JOIN (
                SELECT id_commun, count(*) as c FROM `liens` as l WHERE l.id_rss IN (
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
    r.date_update as pubdateiso,
    r.active,
    count(*) AS nb_items FROM `liens` as l LEFT JOIN rss as r ON l.id_rss = r.id where r.id IS NOT NULL $condActive group by id_rss");

    $results = array();
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $results[$row['id']] =  $row;
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


