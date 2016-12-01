<?php

namespace Shaarlo\Controller;

/**
 * Class SubscriptionController.
 */
class SubscriptionController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (getUtilisateurId() === '') {
            header('Location: index.php');

            return;
        }

        getSession();

        global $SHAARLO_DOMAIN;
        $infoAboutAll = file_get_contents('http://'.$SHAARLO_DOMAIN.'/api.php?do=getInfoAboutAll');
        $infoAboutAll = remove_utf8_bom($infoAboutAll);
        $infoAboutAllDecoded = json_decode($infoAboutAll, true);


        if (!empty($_POST)) {
            if (isset($_POST['shaarlistes'])) {
                $abonnements = $_POST['shaarlistes'];
            } else {
                $abonnements = array();
            }
            majAbonnements($abonnements);
        }

        $mesAbonnements = getAbonnements();

        if ($this->get('shaarliste')) {
            $shaarliste = $this->get('shaarliste');
            // Récupération en bdd
            $abonnements = getAbonnementsByShaarlieurId($shaarliste);

            // On filtre les abonnements à afficher dans le cas des abonnements d'une personne
            foreach ($infoAboutAllDecoded['stat'] as $s => $shaarli) {
                if(!in_array($shaarli['id'], $abonnements)) {
                    unset($infoAboutAllDecoded['stat'][$s]);
                }
            }
        } else {
            //Récupération dans la session
            $abonnements = $mesAbonnements;
            $shaarliste = getUtilisateurId();
        }
        $nbAbonnements = count($mesAbonnements);

        $isMe = false;
        if ($shaarliste === getUtilisateurId()) {
            $isMe = true;
        }

        $infoAboutAllDecodedChunked = array_chunk($infoAboutAllDecoded['stat'],  4);

        $this->render(
            array('nbAbonnements' => $nbAbonnements,
                'infoAboutAllDecodedChunked' => $infoAboutAllDecodedChunked,
                'abonnements' => $abonnements,
                'mes_abonnements' => $mesAbonnements,
                'shaarliste' => $shaarliste,
                'is_me' => $isMe,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
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
                <h1>Abonnements de <?php eh($params['shaarliste']); ?></h1>
                <p>
                    Sélectionnez les shaarlistes que vous souhaitez suivre.
                </p>
                <p>Vous suivez actuellement <span id="span-nbabonnements"><?php echo $params['nbAbonnements']; ?></span> shaarliste(s)</p>
            </div>
        </div>
        <form id="form-abonnements" method="POST">
            <?php
            $this->renderMegaListeShaarlistes($params);
            ?>
        </form>
        <?php
        $this->renderScript();
        ?>
        </body>
        </html>
        <?php
    }

    /**
     * @param array $params
     */
    public function renderMegaListeShaarlistes($params)
    {
        ?>
        <div class="row">
            <div class="column large-3">
                <?php if (empty($params['creation'])) { ?>
                    <a href="index.php" id="a-voir-river" class="button success hidden">Voir la river</a>
                <?php } ?>
            </div>
            <hr/>
            <?php if (empty($params['abonnements'])) { ?>
                <div class="column large-9 text-right">
                    <input id="button-tous" type="button" class="button " value="Tout cocher" />
                    <?php if (!empty($params['abonnements'])) { ?>
                        <input id="button-personne" type="button" class="button " value="Tout décocher" />
                    <?php } ?>
                    <input type="hidden" value="null" name="null" />
                </div>
            <?php } ?>
        </div>
        <?php
        $dateDuJour = new \DateTime();
        $dateDuJour->modify('-2 days');

        foreach ($params['infoAboutAllDecodedChunked'] as $shaarlistes) {
            ?><div class="row" data-equalizer><?php
            foreach ($shaarlistes as $shaarliste) {
                $faviconIcoPath = sprintf('img/favicon/%s.ico', $shaarliste['id']);
                if(!is_file($faviconIcoPath) || filesize($faviconIcoPath) <= 1000) {
                    $faviconIcoPath = sprintf('img/favicon/%s.ico', '7280d5cfd1c82734436f0e19cb14a913');
                }
                ?>
                <div class="column large-3"  >
                    <div class="panel shaarliste-selection <?php if(!in_array($shaarliste['id'], $params['mes_abonnements'])) { echo 'not-selected'; }?>" id="shaarliste-selection-<?php echo $shaarliste['id'];?>" data-equalizer-watch data-shaarliste-id="<?php echo $shaarliste['id'];?>">
                        <div class="row">
                            <div class="column large-10 small-9">
                                <div class="row">
                                    <?php if (displayImages()) { ?>
                                        <div class="column large-12 small-3 medium-text-center">
                                            <a target="_blank" href="<?php eh($shaarliste['link']);?>"><img class="super-entete-avatar" width="64" height="64" src="<?php eh($faviconIcoPath); ?>" /></a>
                                        </div>
                                    <?php } ?>
                                    <div class="column large-12 small-9 medium-text-center">
                                        <div class="row">
                                            <span><?php echo $shaarliste['title'];?></span>
                                        </div>
                                        <div class="row">
                                            <span class="tiny"><?php echo $shaarliste['nb_items'];?> liens sur le site</span>
                                        </div>
                                        <div class="row">
                                            <span class="tiny"><?php echo $shaarliste['nb_followers'];?> abonnés</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="column large-2 small-3">
                                <div class="row text-center">
                                    <a href="#" class="icon-gear" data-dropdown="drop-<?php echo $shaarliste['id'];?>" aria-controls="drop-<?php echo $shaarliste['id'];?>" aria-expanded="false">&nbsp;</a>
                                </div>
                            </div>
                            <ul id="drop-<?php echo $shaarliste['id'];?>" class="f-dropdown" data-dropdown-content aria-hidden="true" tabindex="-1" aria-autoclose="false">
                                <?php
                                if (!empty($shaarliste['pseudo']) && !empty($shaarliste['pwd'])) {
                                    ?>
                                    <li><a href="dashboard.php?shaarliste=<?php eh($shaarliste['pseudo']);?>">Voir profil</a></li>
                                    <?php
                                }
                                ?>
                                <?php if(!in_array($shaarliste['id'], $params['mes_abonnements'])) { ?>
                                    <li><a class="a-add-abonnement" data-shaarliste-id="<?php echo $shaarliste['id'];?>" href="#">S'abonner</a></li>
                                    <?php
                                } else {
                                    ?>
                                    <li><a class="a-add-abonnement" data-shaarliste-id="<?php echo $shaarliste['id'];?>" href="#">Ne plus suivre</a></li>
                                    <?php
                                }
                                ?>
                            </ul>
                            <input style="display:none;" <?php if(in_array($shaarliste['id'], $params['abonnements'])) { echo 'checked="checked"'; }?> type="checkbox" name="shaarlistes[]" value="<?php echo $shaarliste['id'];?>" id="<?php echo $shaarliste['id'];?>" class="checkbox-shaarliste" />
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
            </div>
            <?php
        }
        ?>
        <?php
    }

    /**
     * {@inheritdoc}
     */
    public static function renderScript($params = array())
    {
        parent::renderScript();
        ?>
        <script>
            function addAbo(that, id, action) {
                var r = new XMLHttpRequest();
                var params = "do="+action+"&id=" + id;
                r.open("POST", "add.php", true);
                r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                r.onreadystatechange = function () {
                    that.attr('data-waiting', 'false');
                    if (r.readyState == 4) {
                        if(r.status == 200){
                            var checkboxId = '#' + that.attr('data-shaarliste-id');
                            if(action == 'add') {
                                that.addClass('selected');
                                $(checkboxId).prop('checked', true);
                                $('#span-nbabonnements').text(parseInt($('#span-nbabonnements').text()) + 1);
                            }else {
                                $('#span-nbabonnements').text(parseInt($('#span-nbabonnements').text()) - 1);
                                $(checkboxId).prop('checked', false);
                                that.removeClass('selected');
                            }
                            return;
                        }
                        else {
                            that.text = '-Erreur-';
                            return;
                        }
                    }
                };
                r.send(params);
            }
            $('.a-add-abonnement').click(function() {
                var checkboxId = '#' + $(this).attr('data-shaarliste-id');

                if ($(this).attr('data-waiting') != 'true') {
                    $(this).attr('data-waiting', 'true');
                    if ($(checkboxId).is(':checked')) {
                        // L'utilisateur se désabonne
                        $(this).text("S'abonner");
                        addAbo($(this), $(this).attr('data-shaarliste-id'), 'delete');
                        $('#shaarliste-selection-' + $(this).attr('data-shaarliste-id')).addClass('not-selected');
                    } else {
                        $(this).text("Ne plus suivre");
                        $('#shaarliste-selection-' + $(this).attr('data-shaarliste-id')).removeClass('not-selected');
                        addAbo($(this), $(this).attr('data-shaarliste-id'), 'add');
                    }
                }
                return false;
            });


            $('#button-tous').click(function() {
                var checkbox = $('.checkbox-shaarliste');
                $.each( checkbox, function( key, value ) {
                    $(value).prop('checked', true);
                    var shaarlisteSelection = '#shaarliste-selection-' + $(value).attr('id');
                    $(shaarlisteSelection).addClass('selected');
                });
                $('#form-abonnements').submit();
            });

            $('#button-personne').click(function() {
                var checkbox = $('.checkbox-shaarliste');
                $.each( checkbox, function( key, value ) {
                    $(value).prop('checked', false);
                    var shaarlisteSelection = '#shaarliste-selection-' + $(value).attr('id');
                    $(shaarlisteSelection).removeClass('selected');
                });
                $('#form-abonnements').submit();
            });
            $(document).foundation();

        </script>
        <?php
    }
}
