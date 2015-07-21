<?php 

require_once('Controller.class.php');

class Abonnements extends Controller
{
    public function run() 
    {
        
        // Acces interdit aux anonymes
        if (getUtilisateurId() === '') {
            header('Location: index.php');
            return;
        }
            
        $params = array();
        
        $badges = array (
                        1 => array('id' => '1',  'title' => 'Offert !', 'img' => 'img/top/top_1.gif', 'disabled' => false),
                        2 => array('id' => '2',  'title' => 'Offert !', 'img' => 'img/top/top_2.gif', 'disabled' => false),
                        3 => array('id' => '3',  'title' => 'Offert !', 'img' => 'img/top/top_3.gif', 'disabled' => false),
                        4 => array('id' => '4',  'title' => 'Offert !', 'img' => 'img/top/top_4.gif', 'disabled' => false), 
                        5 => array('id' => '5',  'title' => 'Offert !', 'img' => 'img/top/top_5.gif', 'disabled' => false), 
                        6 => array('id' => '6',  'title' => 'Offert !', 'img' => 'img/top/top_6.gif', 'disabled' => false), 
                        7 => array('id' => '7',  'title' => 'Offert !', 'img' => 'img/top/top_7.gif', 'disabled' => false), 
                        8 => array('id' => '8',  'title' => 'Offert !', 'img' => 'img/top/top_8.gif', 'disabled' => false), 
                        9 => array('id' => '9',  'title' => 'Offert !', 'img' => 'img/top/top_9.gif', 'disabled' => false), 
                        10 => array('id' => '10','title' => 'Offert !', 'img' => 'img/top/top_10.gif', 'disabled' => false), 
                        11 => array('id' => '11', 'img' => 'img/top/top_11.gif', 'disabled' => true, 'tag' => array('video' => 5)),
                        12 => array('id' => '12', 'img' => 'img/top/top_12.gif', 'disabled' => true, 'tag' => array('internet' => 5)),
                        13 => array('id' => '13', 'img' => 'img/top/top_13.gif', 'disabled' => true, 'tag' => array('linux' => 5)),
                        14 => array('id' => '14', 'img' => 'img/top/top_14.gif', 'disabled' => true, 'tag' => array('politique' => 5)),
                        15 => array('id' => '15', 'img' => 'img/top/top_15.gif', 'disabled' => true, 'tag' => array('web' => 5)),
                        16 => array('id' => '16', 'img' => 'img/top/top_16.gif', 'disabled' => true, 'tag' => array('science' => 5)),
                        17 => array('id' => '17', 'img' => 'img/top/top_17.gif', 'disabled' => true, 'tag' => array('sécurité' => 5)),
                        18 => array('id' => '18', 'title' => 'Offert !', 'img' => 'img/top/top_18.gif', 'disabled' => false),
                        19 => array('id' => '19', 'img' => 'img/top/top_19.gif', 'disabled' => true, 'tag' => array('chat' => 5)),
                        20 => array('id' => '20', 'img' => 'img/top/top_20.gif', 'disabled' => true, 'tag' => array('surveillance' => 5)),
                        21 => array('id' => '21', 'img' => 'img/top/top_21.gif', 'disabled' => true, 'tag' => array('android' => 5)),
                        22 => array('id' => '22', 'img' => 'img/top/top_22.gif', 'disabled' => true, 'tag' => array('photo' => 5)),
                        23 => array('id' => '23', 'img' => 'img/top/top_23.gif', 'disabled' => true, 'tag' => array('humour' => 5)),
                        24 => array('id' => '24', 'img' => 'img/top/top_24.gif', 'disabled' => true, 'tag' => array('fun' => 5)),
                        25 => array('id' => '25', 'img' => 'img/top/top_25.gif', 'disabled' => true, 'tag' => array('python' => 5)),
                        26 => array('id' => '26', 'img' => 'img/top/top_26.gif', 'disabled' => true, 'tag' => array('france' => 5)),
                        27 => array('id' => '27', 'title' => 'Offert !', 'img' => 'img/top/top_27.gif', 'disabled' => false),
                        28 => array('id' => '28', 'img' => 'img/top/top_28.gif', 'disabled' => true, 'tag' => array('musique' => 2)),
                        29 => array('id' => '29', 'img' => 'img/top/top_29.gif', 'disabled' => true, 'tag' => array('musique' => 5)),
                        30 => array('id' => '30', 'img' => 'img/top/top_30.gif', 'disabled' => true, 'tag' => array('musique' => 10)),
                        31 => array('id' => '31', 'img' => 'img/top/top_31.gif', 'disabled' => true, 'tag' => array('diy' => 5)),
                        32 => array('id' => '32', 'img' => 'img/top/top_32.gif', 'disabled' => true, 'tag' => array('wtf' => 5)),
                        33 => array('id' => '33', 'img' => 'img/top/top_33.gif', 'disabled' => true, 'tag' => array('privacy' => 5)),
                        34 => array('id' => '34', 'title' => 'Offert !', 'img' => 'img/top/top_34.gif', 'disabled' => false),
                        35 => array('id' => '35', 'img' => 'img/top/top_35.gif', 'disabled' => true, 'tag' => array('opensource' => 5)),
                        36 => array('id' => '36', 'img' => 'img/top/top_36.gif', 'disabled' => true, 'tag' => array('css' => 5)),
                        37 => array('id' => '37','title' => 'Offert !', 'img' => 'img/top/top_37.gif', 'disabled' => false),
                        38 => array('id' => '38', 'img' => 'img/top/top_38.gif', 'disabled' => true, 'tag' => array('javascript' => 5)),
                        39 => array('id' => '39', 'img' => 'img/top/top_39.gif', 'disabled' => true, 'tag' => array('google' => 5)),
                        40 => array('id' => '40', 'img' => 'img/top/top_40.gif', 'disabled' => true, 'tag' => array('image' => 5)),
                        41 => array('id' => '41', 'img' => 'img/top/top_41.gif', 'disabled' => true, 'tag' => array('jeu' => 5)),
                        42 => array('id' => '42', 'img' => 'img/top/top_42.gif', 'disabled' => true, 'tag' => array('shaarli' => 5)),
                        43 => array('id' => '43', 'title' => 'Offert !', 'img' => 'img/top/top_43.gif', 'disabled' => false),
                        44 => array('id' => '44', 'img' => 'img/top/top_44.gif', 'disabled' => true, 'tag' => array('loi' => 5)),
                        45 => array('id' => '45', 'img' => 'img/top/top_45.gif', 'disabled' => true, 'tag' => array('git' => 5)),
                        46 => array('id' => '46', 'title' => 'Offert !', 'img' => 'img/top/top_46.gif', 'disabled' => false),
                        47 => array('id' => '47', 'img' => 'img/top/top_47.gif', 'disabled' => true, 'tag' => array('livre' => 5)),
                        48 => array('id' => '48', 'img' => 'img/top/top_48.gif', 'disabled' => true, 'tag' => array('bd' => 5)),
                        49 => array('id' => '49', 'img' => 'img/top/top_49.gif', 'disabled' => true, 'tag' => array('culture' => 5)),
                        50 => array('id' => '50', 'title' => 'Offert !', 'img' => 'img/top/top_50.gif', 'disabled' => false),
                        51 => array('id' => '51', 'img' => 'img/top/top_51.gif', 'disabled' => true, 'tag' => array('art' => 5)),
                        52 => array('id' => '52', 'img' => 'img/top/top_52.gif', 'disabled' => true, 'tag' => array('justice' => 5)),
                        53 => array('id' => '53', 'img' => 'img/top/top_53.gif', 'disabled' => true, 'tag' => array('santé' => 5)),
                        54 => array('id' => '54', 'title' => 'Offert !', 'img' => 'img/top/top_54.gif', 'disabled' => false),
                        55 => array('id' => '55', 'img' => 'img/top/top_55.gif', 'disabled' => true, 'tag' => array('histoire' => 5)),
                        56 => array('id' => '56', 'img' => 'img/top/top_56.gif', 'disabled' => true, 'tag' => array('opensource' => 10)),
                        57 => array('id' => '57', 'img' => 'img/top/top_57.gif', 'disabled' => true, 'tag' => array('serveur' => 5)),
                        58 => array('id' => '58', 'img' => 'img/top/top_58.gif', 'disabled' => true, 'tag' => array('sysadmin' => 5)),
                        59 => array('id' => '59', 'img' => 'img/top/top_59.gif', 'disabled' => true, 'tag' => array('tuto' => 5)),
                        60 => array('id' => '60', 'img' => 'img/top/top_60.gif', 'disabled' => true, 'tag' => array('firefox' => 5)),
                        61 => array('id' => '61', 'title' => 'Offert ! XD', 'img' => 'img/top/top_61.gif', 'disabled' => false),
                        62 => array('id' => '62', 'img' => 'img/top/top_62.gif', 'disabled' => true, 'tag' => array('blog' => 5)),
                        63 => array('id' => '63', 'img' => 'img/top/top_63.gif', 'disabled' => true, 'tag' => array('youtube' => 5)),
                        64 => array('id' => '64', 'img' => 'img/top/top_64.gif', 'disabled' => true, 'tag' => array('software' => 5)),
                        65 => array('id' => '65', 'img' => 'img/top/top_65.gif', 'disabled' => true, 'tag' => array('ecologie' => 5)),
                        66 => array('id' => '66', 'title' => 'Offert !', 'img' => 'img/top/top_66.gif', 'disabled' => false),
                        67 => array('id' => '67', 'img' => 'img/top/top_67.gif', 'disabled' => true, 'tag' => array('windows' => 5)),
                        68 => array('id' => '68', 'img' => 'img/top/top_68.gif', 'disabled' => true, 'tag' => array('raspberry' => 5)),
                        69 => array('id' => '69', 'img' => 'img/top/top_69.gif', 'disabled' => true, 'tag' => array('php' => 5)),
        );
        
        $currentBadge = getCurrentBadge();
        
        // On construit un tableau d'association badge / tag
        $associationsBadgeTag = array();
        foreach ($badges as $idBadge => $badge) {
            if (isset($badge['tag'])) {
                foreach ($badge['tag'] as $tag => $minOccurence) {
                    if (!isset($associationsBadgeTag[$tag])) {
                        $associationsBadgeTag[$tag] = array();
                    }
                    $associationsBadgeTag[$tag][$idBadge] = $minOccurence;
                }
            }
        }
        $tags = array_keys($associationsBadgeTag);
        $topTags = getTopTagsFromTags($tags);

        /*
         * Affectation des badges en fonctions des tags
         */
        // On regarde pour chaque badge s'il est actif ou pas
        foreach ($badges as $idBadge => $badge) {
            if (isset($badge['tag'])) {
                foreach ($badge['tag'] as $tag => $minOccurence) {
                    // Si le tag du badge n'est pas trouvé, on passe au badge suivant
                    if (!isset($topTags[md5(strtolower($tag))]['c'])) {
                        if (empty($badges[$idBadge]['title'])) {
                            $badges[$idBadge]['title']  = "Condition : Lire sur $minOccurence article(s) sur le thème $tag";
                        } else {
                            $badges[$idBadge]['title'] .= " et $minOccurence article(s) sur le thème $tag";
                        }
                        continue 2;
                    }
                    // Si le tag est trouvé, on réduit la condition
                    if($topTags[md5(strtolower($tag))]['c'] < $minOccurence) {
                        $differenceOccurence = $minOccurence - $topTags[md5(strtolower($tag))]['c'];
                        if (empty($badges[$idBadge]['title'])) {
                            $badges[$idBadge]['title']  = "Condition : Lire $differenceOccurence article(s) sur le thème $tag";
                        } else {
                            $badges[$idBadge]['title'] .= " et $differenceOccurence article(s) sur le thème $tag";
                        }
                        continue 2; // On passe au badge suivant
                    }

                    // Si le tag est trouvé et que la condition est remplie, on informe l'utilisateur
                    if($topTags[md5(strtolower($tag))]['c'] >= $minOccurence) {
                        $differenceOccurence = $minOccurence - $topTags[md5(strtolower($tag))]['c'];
                        if (empty($badges[$idBadge]['title'])) {
                            $badges[$idBadge]['title']  = "Condition : Lire $minOccurence article(s) sur le thème $tag";
                        } else {
                            $badges[$idBadge]['title'] .= " et $minOccurence article(s) sur le thème $tag";
                        }
                    }
                }
                $badges[$idBadge]['title'] .= ' - Gagné !';
                // Si on arrive jusqu'ici c'est que toutes les conditions de tags sont ok
                $badges[$idBadge]['disabled'] = false;
            }
        }


        $this->render(
            array(
                'badges' => $badges,
                'currentBadge' => $currentBadge
            )
        );
    }

    public function render($params=array())
    {
        ?>
        <!doctype html>
        <html class="no-js" lang="en">
            <?php
            $this->renderHead();
            ?>
            <body>
                <?php
                $this->renderMenu();
                ?>

                <div class="row">
                    <div class="column large-12 text-center">
                        <h1>La page des gifs dégueux</h1>
                    </div>
                </div>
                <hr/>
                <?php
                if (!empty($params['badges'])) {
                    $badgesChunked = array_chunk($params['badges'],  4);
                    foreach ($badgesChunked as $badgesPart) {
                        ?>
                        <div class="row">
                            <div class="columns large-12 center">
                                <div class="row" data-equalizer>
                                    <?php
                                    foreach ($badgesPart as $badge) {
                                        if (!is_file($badge['img'])) {
                                            break;
                                        }
                                        ?>
                                        <div class="column large-3 text-center"  >
                                            <div data-badge-id="<?php echo $badge['id']; ?>" class="<?php if ($params['currentBadge'] == $badge['id']) { echo 'selected'; }?> <?php if ($badge['disabled']) { echo 'disabled'; }?> panel badge-selection" title="<?php echo $badge['title']; ?>" data-equalizer-watch>
                                                <div class="row">
                                                    <img src="<?php echo $badge['img']; ?>" />
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
                <?php
                $this->renderScript();
                ?>
            </body>
        </html>
            <?php
    }

    public static function renderScript()
    {
        parent::renderScript();
        ?>
        <script>

            $('.badge-selection').not( ".disabled" ).click(function() {
                var that = $(this);
                if (that.attr('data-waiting') != 'true') {
                    that.attr('data-waiting', 'true');
                    if (!that.hasClass('selected')) {
                        changeBadge($(this), 'badge', that.attr('data-badge-id'));
                    }
                }
            });

        function changeBadge(that, action, value) {
            var r = new XMLHttpRequest(); 
            var params = "do="+action + "&value="+value+ "&state="+value;
            r.open("POST", "add.php", true); 
            r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            r.onreadystatechange = function () {
                if (r.readyState == 4) {
                    if(r.status == 200){
                        $('.selected').removeClass('selected');
                        that.attr('data-waiting', '');
                        that.addClass('selected');
                        return; 
                    }
                    else {
                        return; 
                    }
                }
            }; 
            r.send(params);
        }
        
            $(document).foundation();
           
        </script>
        <?php
    }
}

$controller = new Abonnements();
$controller->run();


