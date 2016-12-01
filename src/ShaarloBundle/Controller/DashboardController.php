<?php

namespace ShaarloBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route("/dashboard")
     */
    public function run()
    {
        $userOptionsUtils = $this->container->get('shaarlo.user_options_utils');
        $shaarloRssUtils = $this->container->get('shaarlo.rss_utils');

        global $SHAARLO_DOMAIN, $API_TRANSFER_PROTOCOL;
        // Accès invité
        if ('enregistrer_temporairement' ===  $this->post('action')) {
            // Connexion invitée
            getSession('', true);
            header('Location: index.php');
            return;
        }

        if ($userOptionsUtils->getUtilisateurId() === '') {
            header('Location: index.php');
            return;
        }


        $pasDeProfil = false;
        if (empty($userOptionsUtils->getAbonnements())) {
            $pasDeProfil = true;
        }

        $shaarliste = $userOptionsUtils->getUtilisateurId();
        if ($this->get('shaarliste')) {
            $shaarliste = $this->get('shaarliste');
        }

        $isMe = false;
        if ($shaarliste === $userOptionsUtils->getUtilisateurId()) {
            $isMe = true;
        }

        $params = array();
        $creation = false;
        // Nouveau message
        if ($this->post('action')) {
            if (isSerieux() && 'report' ===  $this->post('action')  && $isMe) {
                $mysqli = shaarliMyConnect();
                $messageEntite = creerMessage($userOptionsUtils->getUtilisateurId(), $this->post('message'));
                $retourInsertion = insertEntite($mysqli, 'message', $messageEntite);
                if ($retourInsertion) {
                    $params['message'] = 'Le message a bien été envoyé';
                } else {
                    $params['message'] = "L'envoi du message a échoué...";
                }
            }

            if ('mon_shaarli' ===  $this->post('action') && !isEnAttenteDeModeration()  && $isMe) {
                if ($this->post('shaarli_url')) {
                    if (!isShaarliste()) {
                        // Premiere soumission du shaarli, il passera par la modération
                        majShaarliUrl($this->post('shaarli_url'));
                    } else {
                        // Deuxieme soumission, on vérifie le flux de prime abord
                        $url = $this->post('shaarli_url');
                        $url = corrigeUrlMy($url);
                        $url = supprimeDernierPointInterrogation($url);
                        if (!empty($url)) {
                            if ($url !== getShaarliUrl()) {
                                $content = getRss($url . '?do=rss');
                                if (empty($content)) {
                                    $params['message'] = "Ce flux n'a pu être récupéré...";
                                } else {
                                    majShaarliUrl($this->post('shaarli_url'), false);
                                }
                            }
                        } else {
                            // Suppression du lien shaarli/profil
                            supprimeShaarliUrl();
                        }
                    }
                }

                // L'utilisateur souhaite limiter les appels à son flux
                if ($this->post('shaarli_delai')) {
                    $shaarliDelai = (int)$this->post('shaarli_delai');
                    if ($shaarliDelai >=1 ) {
                        majShaarliDelai($shaarliDelai);
                    }
                }
            }
            // Enregistrement d'un nouveau profil
            if ('enregistrer' ===  $this->post('action') ) {
                $password = '';
                if ($this->post('password')) {
                    $password = $this->post('password');
                }
                $shaarliste = $this->post('profil_id');
                $session = getSession($this->post('profil_id'), false, $password, true);
                if ($session !== 401) {
                    $abonnements = $this->post('shaarlistes');
                    majAbonnements($abonnements);
                    setcookie('shaarlieur', $this->post('profil_id'), time()+31536000, $SHAARLO_DOMAIN);
                    $params['message'] = "Votre profil vient d'être créé";
                    $pasDeProfil = false;
                } else {
                    header('Location:dashboard?action=creation&erreur=profil_exists');
                    return;
                }

            }

            // Enregistrement d'un password
            if ('enregistrer_password' ===  $this->post('action')  && $isMe) {
                $password = '';
                $passwordVerif = '';
                if ($this->post('password')) {
                    $password = $this->post('password');
                }
                if ($this->post('password_verif')) {
                    $passwordVerif = $this->post('password_verif');
                }

                // On vérifie que le gus a tapé deux fois le même mot de passe
                if ($passwordVerif === $password) {
                    $oldPassword = '';
                    if ($this->post('old_password')) {
                        $oldPassword = $this->post('old_password');
                    }
                    if ($userOptionsUtils->verifyPassword($userOptionsUtils->getUtilisateurId(), $oldPassword)) {
                        $session = $userOptionsUtils->updatePassword($userOptionsUtils->getUtilisateurId(), $password);
                        $params['message'] = "Le pwd est enregistré";
                    } else {
                        $params['message'] = "L'ancien password est erroné";
                    }
                } else {
                    $params['message'] = "Les deux mots de passe ne concordent pas";
                }
            }

            // Enregistrement d'un email
            if ('enregistrer_email' ===  $this->post('action') && $isMe) {
                $email = '';
                if ($this->post('email')) {
                    $email = $this->post('email');
                }
                if (isValidEmail($email)) {
                    $session = $userOptionsUtils->updateEmail(getUtilisateurId(), $email);
                    $params['message'] = "L'adresse email est enregistrée";
                } else {
                    $params['message'] = "L'adresse email est invalide";
                }
            }


            // Maj filtre des tags
            if ('enregistrer_tags' ===  $this->post('action') && $isMe) {
                $session = $userOptionsUtils->getSession($this->post('profil_id'));
                $userOptionsUtils->updateTags($this->post('tags'));
                $userOptionsUtils->updateNotAllowedTags($this->post('not_allowed_tags'));
                $params['message'] = "La liste des tags à ignorer a été mise à jour";
            }
            // Maj filtre des urls
            if ('enregistrer_urls' ===  $this->post('action') && $isMe) {
                $session = $userOptionsUtils->getSession($this->post('profil_id'));
                $userOptionsUtils->updateNotAllowedUrls($this->post('not_allowed_urls'));
                $params['message'] = "La liste des sites web à ignorer a été mise à jour";
            }
        }

        if ($this->get('action')) {
            // Connexion
            if ('connexion' ===  $this->get('action')) {
                $password = null;
                if ($this->post('password')) {
                    $password = $this->post('password');
                }
                $session = getSession($this->get('profil_id'), true, $password);
                if ($session !== 401) {
                    setcookie('shaarlieur', $this->get('profil_id'), time()+31536000, $SHAARLO_DOMAIN);
                    header('Location: index.php');
                    return;
                }

                $params['message'] = "Ce compte est protégé par un mot de passe";
                $params['demande_password'] = "Ce compte est protégé par un mot de passe";
            }

            // Création d'un nouveau profil
            if ('creation' ===  $this->get('action')) {
                $creation = true;
            }
            // Message d'erreur apres redirection
            if ($this->get('erreur') && $this->get('erreur') == 'profil_exists') {
                $params['message'] = "Ce profil existe deja, merci de choisir un autre pseudo";
            }

            // Annulation demande de modération
            if ('cancel' === $this->get('action') && $isMe) {
                $userOptionsUtils->cancelShaarliUrl();
                header('Location:dashboard.php');
                return;
            }

            // Envoie du mail à l'utilisateur
            if ($pasDeProfil && 'send_pwd_mail' === $this->get('action') && !empty($this->get('profil_id'))) {
                if ($userOptionsUtils->profilHasEmail($this->get('profil_id'))) {
                    // Envoie du mail
                    $email = $userOptionsUtils->profilGetEmail($this->get('profil_id'));
                    $nouveauPassword = uniqid();
                    $retourEnvoi = envoieMailRecuperation($email, $this->get('profil_id'), $nouveauPassword);
                    if ($retourEnvoi === true) {
                        $emailObfusque = obfusqueEmail($email);
                        updateNewPassword($this->get('profil_id'), $nouveauPassword);
                        $params['message'] = "Un email vient d'être envoyé à votre adresse : $emailObfusque";

                    } else {
                        $params['message'] = "L'envoi de l'email de récupération a échoué, merci de réessayer plus tard...";
                    }
                }
            }
        }


        $params['shaarlieurPositionTop'] = $userOptionsUtils->getShaarlieurPositionTop();
        $apiUrl = $this->getParameter('api_url');
        $infoAboutAll = file_get_contents($apiUrl.'?do=getInfoAboutAll');
        $infoAboutAll = $shaarloRssUtils->remove_utf8_bom($infoAboutAll);
        $infoAboutAllDecoded = json_decode($infoAboutAll, true);
        $infoAboutAllDecodedChunked = array();
        if (isset($infoAboutAllDecoded['stat'])) {
            $infoAboutAllDecodedChunked = array_chunk($infoAboutAllDecoded['stat'],  4);
        }
        $params['infoAboutAllDecodedChunked'] = $infoAboutAllDecodedChunked;
        $params['abonnements'] = array();

        $idRssOk = $userOptionsUtils->getIdOkRss($shaarliste);

        $params['id_rss_ok'] = $idRssOk;
        $params['id_rss'] = $userOptionsUtils->getIdRss();
        $params['shaarli_url'] = $userOptionsUtils->getShaarliUrl();
        $params['shaarli_delai'] = $userOptionsUtils->getShaarliDelai();
        $params['pas_de_profil'] = $pasDeProfil;
        $params['creation'] = $creation;
        $params['currentBadge'] = $userOptionsUtils->getCurrentBadge();
        $params['script'] = '';

        $params['shaarli_url_ok'] = $userOptionsUtils->getShaarliUrlOk($shaarliste);

        $params['nb_abonnements'] = $userOptionsUtils->getNbAbonnements($shaarliste);
        $params['shaarliste'] = $shaarliste;

        $abonnements = $userOptionsUtils->getAbonnements();
        $params['nb_abonnes'] = $userOptionsUtils->getNbAbonnes($idRssOk);
        if(in_array($idRssOk , $abonnements)) {
            $params['est_abonne'] = true;
        } else {
            $params['est_abonne'] = false;
        }

         /*
         * Bloc top shaarlieur
         */
        $idBadge = 1;
        if (!empty($params['shaarlieurPositionTop']) && is_file(sprintf('img/top/top_%s.gif', $params['shaarlieurPositionTop']))) {
            $idBadge = $params['shaarlieurPositionTop'];
        }
        if (!is_null($params['currentBadge'])) {
            $idBadge = $params['currentBadge'];
        }

        $params['image_top_gif'] = null;
        $nomImageTopGif = sprintf('img/top/top_%s.gif', $idBadge);
        if (is_file($nomImageTopGif)) {
            $params['image_top_gif'] = $nomImageTopGif;
        }

        $params['is_me'] = $isMe;
        $params['profil_image'] = $userOptionsUtils->getImageProfilSrc($shaarliste);
        $params['displayShaarlistesNonSuivis'] = $userOptionsUtils->displayShaarlistesNonSuivis();
        $params['displayBestArticle'] = $userOptionsUtils->displayBestArticle();
        $params['displayBlocConversation'] = $userOptionsUtils->displayBlocConversation();
        $params['displayEmptyDescription'] = $userOptionsUtils->displayEmptyDescription();
        $params['displayOnlyNewArticles'] = $userOptionsUtils->displayOnlyNewArticles();
        $params['useTopButtons'] = $userOptionsUtils->useTopButtons();
        $params['useRefreshButton'] = $userOptionsUtils->useRefreshButton();
        $params['displayDiscussions'] = $userOptionsUtils->displayDiscussions();
        $params['isInscriptionAuto'] = $userOptionsUtils->isInscriptionAuto();
        $params['isMenuLocked'] = $userOptionsUtils->isMenuLocked();
        $params['displayRssButton'] = $userOptionsUtils->displayRssButton();
        $params['isExtended'] = $userOptionsUtils->isExtended();
        $params['useScrollInfini'] = $userOptionsUtils->useScrollInfini();
        $params['displayImages'] = $userOptionsUtils->displayImages();
        $params['displayLittleImages'] = $userOptionsUtils->displayLittleImages();
        $params['displayOnlyUnreadArticles'] = $userOptionsUtils->displayOnlyUnreadArticles();
        $params['useElevator'] = $userOptionsUtils->useElevator();
        $params['useDotsies'] = $userOptionsUtils->useDotsies();
        $params['useScrollInfini'] = $userOptionsUtils->useScrollInfini();
        $params['isOnRiver'] = $userOptionsUtils->isOnRiver();
        $params['isOnAbonnements'] = $userOptionsUtils->isOnAbonnements();
        $params['tags_list'] = implode(' ', $userOptionsUtils->getTags());
        $params['not_tags_list'] = implode(' ', $userOptionsUtils->getNotAllowedTags());


        return $this->render(
                '@Shaarlo/dashboard.html.twig',
                array_merge($this->getGlobalTemplateParameters(),
                        $params
                )
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function renderScript($params = array())
    {
        parent::renderScript();
        ?>
        <script>
            $('input[data-option]').click(function() {
                addOption($(this), $(this).attr('data-option'), $(this).val());
            });

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
                                $('#a-voir-river').removeClass('hidden');
                                $('#input-enregistrer-profil').prop('disabled', false);
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
            $('.shaarliste-selection').click(function() {
                var checkboxId = '#' + $(this).attr('data-shaarliste-id');

                if ($(this).attr('data-waiting') != 'true') {
                    $(this).attr('data-waiting', 'true');
                    if ($(checkboxId).is(':checked')) {
                        addAbo($(this), $(this).attr('data-shaarliste-id'), 'delete');
                    } else {
                        addAbo($(this), $(this).attr('data-shaarliste-id'), 'add');
                    }
                }
            });


            $('#button-synchro-favicon').click(function() {
                var r = new XMLHttpRequest();
                var params = "";
                $('#img-synchro-favicon').show();
                r.open("POST", "synchro_favicon.php", true);
                r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                r.onreadystatechange = function () {
                    $('#img-synchro-favicon').hide();
                    if (r.readyState == 4) {
                        if(r.status == 200){
                            return;
                        }
                        else {
                            return;
                        }
                    }
                };
                r.send(params);
            });

            $('#button-synchro-shaarli').click(function() {
                var r = new XMLHttpRequest();
                var params = "";
                $('#img-synchro-shaarli').show();
                r.open("POST", "synchro_shaarli.php", true);
                r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                r.onreadystatechange = function () {
                    $('#img-synchro-shaarli').hide();
                    if (r.readyState == 4) {
                        if(r.status == 200){
                            $('#msg-synchro-shaarli').css('color', 'green');
                            $('#msg-synchro-shaarli').text('OK');
                            return;
                        }
                        else {
                            $('#msg-synchro-shaarli').css('color', 'red');
                            $('#msg-synchro-shaarli').text('Flux en erreur...');
                            return;
                        }
                    }
                };
                r.send(params);
            });

            $('#button-tous').click(function() {
                var checkbox = $('.checkbox-shaarliste');
                $.each( checkbox, function( key, value ) {
                    $(value).prop('checked', true);
                    var shaarlisteSelection = '#shaarliste-selection-' + $(value).attr('id');
                    $(shaarlisteSelection).addClass('selected');
                });
                <?php
                if (!$params['creation']) {
                ?>
                $('#form-abonnements').submit();
                <?php
                } else {
                ?>
                /* On va directement en fin de page */
                $("html, body").animate({ scrollTop: $(document).height()-$(window).height() });
                <?php }?>

                $('#input-enregistrer-profil').prop('disabled', false);
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

            function blocShow() {
                if ('#pwd' == location.hash) {
                    $(".bloc-show").hide();
                    $("#pwd").show();
                }
                if ('#options' == location.hash) {
                    $(".bloc-show").hide();
                    $("#options").show();
                }
                if ('#report' == location.hash) {
                    $(".bloc-show").hide();
                    $("#report").show();
                }
                if ('#filtres' == location.hash) {
                    $(".bloc-show").hide();
                    $("#filtres").show();
                }
                if ('#modifier-shaarli' == location.hash) {
                    $(".bloc-show").hide();
                    $("#modifier-shaarli").show();
                }
                if ('#mon-shaarli' == location.hash) {
                    $(".bloc-show").hide();
                    $("#mon-shaarli").show();
                }
            }

            $('.link-show').click(function(event) {
                $(".bloc-show").hide();
                $($(this).attr('href')).show();
                event.preventDefault();
            });

            $(".bloc-show").hide();
            blocShow();

        </script>
        <?php
    }
}