<?php

namespace ShaarloBundle\Utils;

class MysqlUtils
{
    /**
     * @var string
     */
    protected $databaseHost;

    /**
     * @var string
     */
    protected $databasePort;

    /**
     * @var string
     */
    protected $databaseName;

    /**
     * @var string
     */
    protected $databaseUser;

    /**
     * @var string
     */
    protected $databasePassword;

    /**
     * MysqlUtils constructor.
     *
     * @param string $databaseHost
     * @param string $databasePort
     * @param string $databaseName
     * @param string $databaseUser
     * @param string $databasePassword
     */
    public function __construct($databaseHost, $databasePort, $databaseName, $databaseUser, $databasePassword)
    {
        $this->databaseHost = $databaseHost;
        $this->databasePort = $databasePort;
        $this->databaseName = $databaseName;
        $this->databaseUser = $databaseUser;
        $this->databasePassword = $databasePassword;
    }

    public function shaarliMyConnect()
    {
        $mysqli = new \mysqli($this->databaseHost, $this->databaseUser, $this->databasePassword, $this->databaseName);
        /* check connection */
        if ($mysqli->connect_errno) {
            return null;
        }

        $mysqli->set_charset("utf8");

        return $mysqli;
    }

    function insertArticles($mysqli, $articles)
    {
        if($mysqli === null) {
            return null;
        }

        $this->insertEntites($mysqli, 'liens', $articles);
    }

    function insertEntite($mysqli, $table, $entite)
    {
        return $this->insertEntites($mysqli, $table, array($entite));
    }

    function insertEntites($mysqli, $table, $entites)
    {
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
            $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE id_commun=VALUES(id_commun), date_update=VALUES(date_update), url_simplifiee=VALUES(url_simplifiee), article_url=VALUES(article_url), article_description=VALUES(article_description), id_rss_origin=VALUES(id_rss_origin), id_rss=VALUES(id_rss), tags=VALUES(tags) ', $table, $requeteClefSQL, implode(',', $sql));
        }elseif($table == 'rss') {
            $requeteSQL = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE date_update=VALUES(date_update)', $table, $requeteClefSQL, implode(',', $sql));
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

    function creerShaarlieurLiensIgnore($idCommun, $idShaarlieur) {
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

    function getLastIdCommunFromIdRss($mysqli, $idRss) {
        $query = sprintf('SELECT id_commun
        FROM liens
        WHERE id_rss="%s"
        ORDER BY date_insert DESC
        LIMIT 1'
            , $idRss
        );

        $articles = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                return $row['id_commun'];
            }
        }

        return null;
    }


    function getAllArticlesDuJour($mysqli, $username=null, $fullText = null, $popularite=0, $orderBy = null, $order='desc', $from=null, $to=null, $limit=null, $tags=null, $onlyNews=false) {
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

        // Jointure sur les articles déjà vus ou à ignorer
        $jointureTags = " LEFT JOIN shaarlieur_liens_ignore AS sli ON l.id_commun=sli.id_commun AND sli.id_shaarlieur='$username' WHERE sli.id_commun is NULL AND ";
        if ($onlyNews) {
            $jointureTags = " LEFT JOIN shaarlieur_liens_ignore AS sli ON l.id_commun=sli.id_commun AND sli.id_shaarlieur='$username' LEFT JOIN shaarlieur_liens_clic AS slc ON l.id_commun=slc.id_commun AND slc.id_shaarlieur='$username' WHERE sli.id_commun is NULL AND slc.id_commun is NULL AND ";
        }

        // Ajout de la contrainte sur les tags
        if (!empty($tags)) {
            $tagsIN = arrayToIN($mysqli, $tags);
            $jointureTags = " JOIN tags ON l.id=tags.id_lien $jointureTags tags.nom $tagsIN AND ";
        }

        if(!is_null($username)) {
            $query = sprintf("SELECT liens.*, rss.rss_titre, mes_rss.alias, rss_origin.rss_titre AS rss_titre_origin, rss_origin.url AS rss_url_origin, mes_rss_origin.alias AS alias_origin,  liens_clic.nb_clic, shaarlieur.id as shaarlieur_pseudo, shaarlieur.pwd as shaarlieur_pwd
        FROM liens
        INNER JOIN (
            SELECT lienseh(.id_commun, count(*) as c from liens INNER JOIN (
                SELECT l.id_commun, count(*) as c FROM `liens` as l $jointureTags l.id_rss IN (
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
        LEFT OUTER JOIN liens_clic ON (liens_clic.id_commun=liens.id_commun)
        LEFT OUTER JOIN shaarlieur ON (liens.id_rss=shaarlieur.shaarli_url_id_ok)
        WHERE rss.active = '1' AND (shaarlieur.shaarli_on_river='1' OR shaarlieur.shaarli_on_river IS NULL)
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

        $query = sprintf("SELECT id_rss from mes_rss JOIN rss ON rss.id=mes_rss.id_rss WHERE mes_rss.username='%s' AND rss.active=1", $mysqli->real_escape_string($username));

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

        $query = sprintf("SELECT url FROM `rss` WHERE active=1 AND `404`=0");

        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $entites[] = $row['url'];
            }
        }

        return $entites;
    }

    /**
     * Retourne le nombre de minutes à attendre
     * entre chaque maj du flux
     *
     * @return int : delai en minutes indiqué par le shaarlieur
     */
    function getDelaiBeforeCall($mysqli, $idRss) {
        $query = sprintf("SELECT shaarli_delai FROM `shaarlieur` WHERE shaarli_url_id_ok='%s' LIMIT 1", $mysqli->real_escape_string($idRss));

        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                return $row['shaarli_delai'];
            }
        }

        return 1;
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
    r.`404` as is_dead,
    r.erreur,
    r.erreur_message,
    r.date_insert as createdateiso,
    r.date_update as pubdateiso,
    r.active,
    l.nb_items AS nb_items,
    sh.id as pseudo,
    sh.pwd as pwd
    FROM `rss` as r
    LEFT JOIN (SELECT id_rss, count(*) AS nb_items FROM `liens` group by `id_rss`) AS l ON l.id_rss = r.id
    LEFT JOIN shaarlieur as sh ON l.id_rss = sh.shaarli_url_id_ok
    where r.id IS NOT NULL AND (sh.shaarli_on_abonnements = '1' OR sh.shaarli_on_abonnements IS NUll) $condActive  AND l.nb_items > 0
    group by r.id
    ORDER BY r.date_insert DESC, r.date_update DESC
    ");

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['pwd'])) {
                    $row['pwd'] = true;
                }
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

    function updateRssErreur($mysqli, $rssId, $erreurMessage) {
        $dateUpdate = date('YmdHis');
        $query = sprintf("UPDATE rss SET erreur=1, erreur_message='%s', date_update='%s' WHERE id='%s'", $mysqli->real_escape_string($erreurMessage), $mysqli->real_escape_string($dateUpdate), $mysqli->real_escape_string($rssId));
        $mysqli->query($query);
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


    function updateRssTitre($mysqli, $idRss, $titre) {
        $dateUpdate = date('YmdHis');
        $query = sprintf("UPDATE rss SET rss_titre='%s', date_update='%s' WHERE id='%s'", $mysqli->real_escape_string($titre), $mysqli->real_escape_string($dateUpdate), $mysqli->real_escape_string($idRss));
        $mysqli->query($query);
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

    function updateShaarlieurEmail($mysqli, $shaarlieurId, $email) {
        $dateUpdate = date('YmdHis');
        $query = sprintf("UPDATE shaarlieur SET email='%s' WHERE id='%s'", $mysqli->real_escape_string($email), $mysqli->real_escape_string($shaarlieurId));
        $mysqli->query($query);
    }


    function majDerniereConnexion($mysqli, $shaarlieurId) {
        $dateDerniereConnexion = date('YmdHis');
        $query = sprintf("UPDATE shaarlieur SET nb_connexion=nb_connexion+1, date_derniere_connexion='%s' WHERE id='%s'", $mysqli->real_escape_string($dateDerniereConnexion), $mysqli->real_escape_string($shaarlieurId));
        $mysqli->query($query);
    }

    function updateShaarlieurShaarliUrl($mysqli, $shaarlieurId, $shaarliUrl) {
        $dateUpdate = date('YmdHis');
        $query = sprintf("UPDATE shaarlieur SET shaarli_url='%s', date_update='%s', soumission_date='%s', shaarli_private='0', shaarli_ok='2' WHERE id='%s'",
            $mysqli->real_escape_string($shaarliUrl),
            $mysqli->real_escape_string($dateUpdate),
            $mysqli->real_escape_string($dateUpdate),
            $mysqli->real_escape_string($shaarlieurId)
        );
        $mysqli->query($query);
    }

    function updateShaarlieurShaarliDelai($mysqli, $shaarlieurId, $shaarliDelai) {
        $dateUpdate = date('YmdHis');
        $query = sprintf("UPDATE shaarlieur SET shaarli_delai='%s', date_update='%s' WHERE id='%s'",
            $mysqli->real_escape_string($shaarliDelai),
            $mysqli->real_escape_string($dateUpdate),
            $mysqli->real_escape_string($shaarlieurId)
        );
        $mysqli->query($query);
    }

    function cancelShaarlieurShaarliUrl($mysqli, $shaarlieurId) {
        $dateUpdate = date('YmdHis');
        $query = sprintf("UPDATE shaarlieur SET shaarli_url=shaarli_url_ok, date_update='%s', shaarli_ok='0' WHERE id='%s'",
            $mysqli->real_escape_string($dateUpdate),
            $mysqli->real_escape_string($shaarlieurId)
        );
        $mysqli->query($query);
    }

    function supprimeShaarlieurShaarliUrl($mysqli, $shaarlieurId) {
        $dateUpdate = date('YmdHis');
        $query = sprintf("UPDATE shaarlieur SET shaarli_url='', shaarli_url_id_ok='', 'shaarli_on_abonnements'='1', 'shaarli_on_river'='1', shaarli_url_ok='', date_update='%s', shaarli_ok='0' WHERE id='%s'",
            $mysqli->real_escape_string($dateUpdate),
            $mysqli->real_escape_string($shaarlieurId)
        );
        $mysqli->query($query);
    }


    function updateShaarlieurShaarliOnAbonnements($mysqli, $shaarlieurId, $isOnAbonnements) {
        $dateUpdate = date('YmdHis');
        if ($isOnAbonnements) {
            $isOnAbonnementsSql = '1';
        } else {
            $isOnAbonnementsSql = '0';
        }
        $query = sprintf("UPDATE shaarlieur SET shaarli_on_abonnements='%s', date_update='%s' WHERE id='%s'",
            $mysqli->real_escape_string($isOnAbonnementsSql),
            $mysqli->real_escape_string($dateUpdate),
            $mysqli->real_escape_string($shaarlieurId)
        );
        $mysqli->query($query);
    }

    function updateShaarlieurShaarliOnRiver($mysqli, $shaarlieurId, $isOnRiver) {
        $dateUpdate = date('YmdHis');
        if ($isOnRiver) {
            $isOnRiverSql = '1';
        } else {
            $isOnRiverSql = '0';
        }
        $query = sprintf("UPDATE shaarlieur SET shaarli_on_river='%s', date_update='%s' WHERE id='%s'",
            $mysqli->real_escape_string($isOnRiverSql),
            $mysqli->real_escape_string($dateUpdate),
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

    function updateShaarlieurShaarliPok($mysqli, $shaarlieurId, $shaarliUrlIdOk, $shaarliUrlOk) {
        $dateUpdate = date('YmdHis');
        $query = sprintf("UPDATE shaarlieur SET shaarli_ok='0', date_update='%s', shaarli_url_ok='' WHERE id='%s'",
            $mysqli->real_escape_string($dateUpdate),
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


    /**
     * Retourne le nombre d'activation/desactivation
     * d'une option disponible dans le dashboad
     *
     * @param $mysqli
     * @param string $option : 'display_best_article'
     *
     * @return array('true' => '10', 'false' => '4')
     */
    function getStatsFromOption($mysqli, $option) {
        $query = sprintf("SELECT * FROM (SELECT count(*) AS 'false' FROM `shaarlieur` WHERE `data` LIKE '%%\"%s\":false%%') AS option_false, (SELECT count(*) AS 'true' FROM `shaarlieur` WHERE `data` LIKE '%%\"%s\":true%%') AS option_true", $mysqli->real_escape_string($option), $mysqli->real_escape_string($option));
        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                return $row;
            }
        }

        return null;
    }


    /**
     * Indique si un lien a déjà été cliqué par un utilisateur
     *
     * @param $mysqli
     * @param string $idCommun
     * @param string $shaarlieurId
     *
     * @return bool true | false
     */
    function isLienDejaClic($mysqli, $idCommun, $shaarlieurId) {
        $query = sprintf("SELECT count(*) AS c FROM shaarlieur_liens_clic WHERE id_shaarlieur='%s' AND id_commun='%s'",
            $mysqli->real_escape_string($shaarlieurId),
            $mysqli->real_escape_string($idCommun)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['c']) && $row['c'] > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retourne le nombre de followers pour chaque flux
     *
     * @param $mysqli
     *
     * @return array('2654874' => 123, ...)
     */
    function getTopRss($mysqli) {
        $query = "SELECT id_rss, count(*) as c FROM `mes_rss` group by id_rss";

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $results[$row['id_rss']] = $row['c'];
            }
        }

        return $results;
    }


    /**
     * Indique si un profil a un email
     *
     * @param $mysqli
     * @param string $shaarlieurId
     *
     * @return bool true | false
     */
    function hasEmailByShaarlieurId($mysqli,  $shaarlieurId) {
        $query = sprintf("SELECT count(*) AS c FROM shaarlieur WHERE id='%s' AND email !=''",
            $mysqli->real_escape_string($shaarlieurId)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['c']) && $row['c'] > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retourne le mail d'un utilisateur
     *
     * @param $mysqli
     * @param string $shaarlieurId
     *
     * @return bool true | false
     */
    function getEmailByShaarlieurId($mysqli,  $shaarlieurId) {
        $query = sprintf("SELECT email FROM shaarlieur WHERE id='%s'",
            $mysqli->real_escape_string($shaarlieurId)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['email'])) {
                    return $row['email'];
                }
            }
        }

        return null;
    }


    /**
     * Retourne les catégories d'un article
     *
     * @param $mysqli
     * @param array $tags
     *
     * @return array('informatique' => '80', 'société' => 20);
     */
    function getCategoriesFromTags($mysqli, $tags)
    {
        $tagsIN = arrayToIN($mysqli, $tags);

        $query = "SELECT categorie, count(*) as c
        FROM categories
        WHERE tag $tagsIN
        GROUP BY categorie";

        $total = 0;
        $results = array();
        $resultsTmp = array();

        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (!isset($resultsTmp[$row['categorie']])) {
                    $resultsTmp[$row['categorie']] = 0;
                }
                $total += $row['c'];
                $resultsTmp[$row['categorie']] += $row['c'];
            }
        }

        foreach ($resultsTmp as $categorie => $c) {
            $results[$categorie] = round($c/$total * 100);
        }


        return $results;
    }

    /**
     * Retourne la catégorie la plus probable d'un article
     *
     * @param $mysqli
     * @param array $tags
     *
     * @return string 'informatique'
     */
    function getTopCategorieFromTags($mysqli, $tags)
    {
        $tagsIN = arrayToIN($mysqli, $tags);
        $query = "SELECT categorie, count(*) as c
        FROM categories
        WHERE tag $tagsIN
        GROUP BY categorie
        order by c
        LIMIT 1
        ";

        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                return $row['categorie'];
            }
        }

        return '';
    }


    /**
     * Retourne le nombre de poussins qu'un utilisateur a utilisé dans la journée
     *
     * @param $mysqli
     * @param string $shaarlieurId
     * @param string $dateJour
     *
     * @return int $count
     */
    function getNbPoussinsUtilisesByShaarlieurId($mysqli, $shaarlieurId, $dateJour)
    {

        $query = sprintf("SELECT count(*) AS c FROM poussins_transactions WHERE pseudo_source='%s' AND date_jour ='%s'",
            $mysqli->real_escape_string($shaarlieurId),
            $mysqli->real_escape_string($dateJour)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['c']) && $row['c'] > 0) {
                    return $row['c'];
                }
            }
        }

        return 0;
    }


    /**
     * Retourne le nombre d'abonné d'un id de flux
     *
     * @param $mysqli
     * @param string $idRss
     *
     * @return int $count
     */
    function getNbAbonnesByIdRss($mysqli, $idRss)
    {

        $query = sprintf("SELECT count(*) AS c FROM mes_rss WHERE id_rss='%s'",
            $mysqli->real_escape_string($idRss)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['c']) && $row['c'] > 0) {
                    return $row['c'];
                }
            }
        }

        return 0;
    }

    /**
     * Retourne le nombre de shaarlistes abonnés à un id de flux
     *
     * @param $mysqli
     * @param string $idRss
     *
     * @return int $count
     */
    function getNbShaarlistesAbonnesByIdRss($mysqli, $idRss)
    {

        $query = sprintf("SELECT count(*) AS c FROM mes_rss JOIN shaarlieur ON mes_rss.username=shaarlieur.id WHERE id_rss='%s' AND shaarlieur.shaarli_ok='1'",
            $mysqli->real_escape_string($idRss)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['c']) && $row['c'] > 0) {
                    return $row['c'];
                }
            }
        }

        return 0;
    }



    /**
     * Retourne le nombre de poussins qu'un utilisateur PEUT utiliser dans la journée
     *
     * @param $mysqli
     * @param string $shaarlieurId
     *
     * @return int $count
     */
    function getNbPoussinsLimiteByShaarlieurId($mysqli,  $shaarlieurId)
    {
        $query = sprintf("SELECT poussins_limite FROM shaarlieur WHERE id='%s' LIMIT 1",
            $mysqli->real_escape_string($shaarlieurId)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['poussins_limite'])) {
                    return $row['poussins_limite'];
                }
            }
        }

        return 0;
    }

    /**
     * Retourne l'id rss de l'utilisateur
     *
     * @param $mysqli
     * @param string $shaarlieurId
     *
     * @return string shaarli_url_id_ok
     */
    function getIdOkRssByShaarlieurId($mysqli,  $shaarlieurId)
    {
        $query = sprintf("SELECT shaarli_url_id_ok FROM shaarlieur WHERE id='%s' LIMIT 1",
            $mysqli->real_escape_string($shaarlieurId)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['shaarli_url_id_ok'])) {
                    return $row['shaarli_url_id_ok'];
                }
            }
        }

        return 0;
    }

    /**
     * Retourne l'url du shaarli de l'utilisateur
     *
     * @param $mysqli
     * @param string $shaarlieurId
     *
     * @return string shaarli_url_id_ok
     */
    function getUrlOkByShaarlieurId($mysqli,  $shaarlieurId)
    {
        $query = sprintf("SELECT shaarli_url_ok FROM shaarlieur WHERE id='%s' LIMIT 1",
            $mysqli->real_escape_string($shaarlieurId)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['shaarli_url_ok'])) {
                    return $row['shaarli_url_ok'];
                }
            }
        }

        return 0;
    }



    /**
     * Créer une transaction poussin
     *
     * @param string $shaarlieurId
     * @param string $shaarlieurCible
     * @param string $dateJour
     * @param string $idLien
     *
     * @return int $count
     */
    function creerTransactionPoussin($shaarlieurId, $shaarlieurCible, $dateJour, $idLien)
    {

        $entite = array('pseudo_source' => $shaarlieurId
        ,'pseudo_cible' => $shaarlieurCible
        ,'date_jour' => $dateJour
        ,'id_lien' => $idLien
        );

        $entite['date_update'] = date('YmdHis');
        $entite['date_insert'] = $entite['date_update'];

        return $entite;
    }


    /**
     * Supprime une transaction poussin
     *
     * @param \mysqli $mysqli
     * @param string $shaarlieurId
     * @param string $shaarlieurCible
     * @param string $dateJour
     *
     * @return bool
     */
    function deleteTransactionPoussin($mysqli, $shaarlieurSource, $shaarlieurCible, $dateJour) {
        $query = sprintf("DELETE FROM poussins_transactions WHERE shaarlieur_cible='%s' AND shaarlieur_source='%s' AND date_jour='%s'",
            $mysqli->real_escape_string($shaarlieurSource),
            $mysqli->real_escape_string($shaarlieurCible),
            $mysqli->real_escape_string($dateJour)
        );

        return $mysqli->query($query);
    }


    /**
     * Ajoute un poussin au solde du shaarlieur
     *
     * @param \mysqli $mysqli
     * @param string $shaarlieurId
     *
     * @return bool
     */
    function addPoussinToShaarlieurByShaarlieurId($mysqli, $shaarlieurId) {
        $dateUpdate = date('YmdHis');

        $query = sprintf("UPDATE shaarlieur SET poussins_solde=poussins_solde+1, date_update='%s' WHERE id='%s'",
            $mysqli->real_escape_string($dateUpdate),
            $mysqli->real_escape_string($shaarlieurId)
        );

        return $mysqli->query($query);
    }


    /**
     * Retourne le solde poussin du shaarlieur
     *
     * @param \mysqli $mysqli
     * @param string $shaarlieurId
     *
     * @return bool
     */
    function getPoussinsSoldeByShaarlieurId($mysqli, $shaarlieurId) {
        $query = sprintf("select poussins_solde from shaarlieur WHERE id='%s' LIMIT 1",
            $mysqli->real_escape_string($shaarlieurId)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['poussins_solde'])) {
                    return $row['poussins_solde'];
                }
            }
        }

        return 0;
    }


    /**
     * Retourne les pseudos poussinés par l'utilisateur
     *
     * @param \mysqli $mysqli
     * @param string $shaarlieurId
     * @param string $dateJour
     *
     * @return bool
     */
    function getShaarlieursPoussinesByShaarlieurId($mysqli, $shaarlieurId, $dateJour) {
        $query = sprintf("select pseudo_cible, id_lien from poussins_transactions WHERE pseudo_source='%s' AND date_jour='%s'",
            $mysqli->real_escape_string($shaarlieurId),
            $mysqli->real_escape_string($dateJour)
        );

        $results = array();
        if ($result = $mysqli->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['pseudo_cible'])) {
                    if (!isset($results[$row['pseudo_cible']])) {
                        $results[$row['pseudo_cible']] = array();
                    }
                    $results[$row['pseudo_cible']][$row['id_lien']] = true;
                }
            }
        }

        return $results;
    }
}