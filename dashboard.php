<?php

require_once('Controller.class.php');
require_once 'config.php';

/*
ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);*/
class Dashboard extends Controller
{
        public function run()
        {
            global $SHAARLO_DOMAIN;
            // Accès invité
            if ('enregistrer_temporairement' ===  $this->post('action')) {
                // Connexion invitée
                getSession('', true);
                header('Location: index.php');
                return;
            }

            if (getUtilisateurId() === '') {
                header('Location: index.php');
                return;
            }


            $pasDeProfil = false;
            if (empty(getAbonnements())) {
                $pasDeProfil = true;
            }
            
            $shaarliste = getUtilisateurId();
            if ($this->get('shaarliste')) {
                $shaarliste = $this->get('shaarliste');
            }

            $isMe = false;
            if ($shaarliste === getUtilisateurId()) {
                $isMe = true;
            }
            
            $params = array();
            $creation = false;
            // Nouveau message
            if ($this->post('action')) {
                if (isSerieux() && 'report' ===  $this->post('action')  && $isMe) {
                    $mysqli = shaarliMyConnect();
                    $messageEntite = creerMessage(getUtilisateurId(), $this->post('message'));
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
                        if (verifyPassword(getUtilisateurId(), $oldPassword)) {
                            $session = updatePassword(getUtilisateurId(), $password);
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
                        $session = updateEmail(getUtilisateurId(), $email);
                        $params['message'] = "L'adresse email est enregistrée";
                    } else {
                        $params['message'] = "L'adresse email est invalide";
                    }
                }


                // Maj filtre des tags
                if ('enregistrer_tags' ===  $this->post('action') && $isMe) {
                    $session = getSession($this->post('profil_id'));
                    updateTags($this->post('tags'));
                    updateNotAllowedTags($this->post('not_allowed_tags'));
                    $params['message'] = "La liste des tags à ignorer a été mise à jour";
                }
                // Maj filtre des urls
                if ('enregistrer_urls' ===  $this->post('action') && $isMe) {
                    $session = getSession($this->post('profil_id'));
                    updateNotAllowedUrls($this->post('not_allowed_urls'));
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
                    cancelShaarliUrl();
                    header('Location:dashboard.php');
                    return;
                }

                // Envoie du mail à l'utilisateur
                if ($pasDeProfil && 'send_pwd_mail' === $this->get('action') && !empty($this->get('profil_id'))) {
                    if (profilHasEmail($this->get('profil_id'))) {
                        // Envoie du mail
                        $email = profilGetEmail($this->get('profil_id'));
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


            $params['shaarlieurPositionTop'] = getShaarlieurPositionTop();
            $infoAboutAll = file_get_contents('https://'.$SHAARLO_DOMAIN.'/api.php?do=getInfoAboutAll');
            $infoAboutAll = remove_utf8_bom($infoAboutAll);
            $infoAboutAllDecoded = json_decode($infoAboutAll, true);
            $infoAboutAllDecodedChunked = array_chunk($infoAboutAllDecoded['stat'],  4);
            $params['infoAboutAllDecodedChunked'] = $infoAboutAllDecodedChunked;
            $params['abonnements'] = array();

            $idRssOk = getIdOkRss($shaarliste);
            
            $params['id_rss_ok'] = $idRssOk;
            $params['id_rss'] = getIdRss();
            $params['shaarli_url'] = getShaarliUrl();
            $params['shaarli_delai'] = getShaarliDelai();
            $params['pas_de_profil'] = $pasDeProfil;
            $params['creation'] = $creation;
            $params['currentBadge'] = getCurrentBadge();
            $params['poussins_solde'] = getPoussinsSolde();
            $params['script'] = '';

            $params['shaarli_url_ok'] = getShaarliUrlOk($shaarliste);
            
            $params['nb_abonnements'] = getNbAbonnements($shaarliste);
            $params['shaarliste'] = $shaarliste;
            
            $abonnements = getAbonnements();
            $params['nb_abonnes'] = getNbAbonnes($idRssOk);
            if(in_array($idRssOk , $abonnements)) {
                $params['est_abonne'] = true;
            } else {
                $params['est_abonne'] = false;
            }
    
            $params['is_me'] = $isMe;
            
            $this->render($params);
        }

        public function render($params=array())
        {
            global $SHAARLO_DOMAIN;

            // Protection des paramètres
            $params = $this->htmlspecialchars($params);

            ?><!doctype html>
            <html class="no-js" lang="en">
                <?php
                    $this->renderHead();
                ?>

                <body>
                <?php
                if (!$params['pas_de_profil'] && !$params['creation']) {
                    $this->renderMenu();
                }
                ?>
                    <?php
                    if (isset($params['message'])) {
                        ?>
                        <div class="row">
                            <div class="columns large-12 center">
                                <div class="panel">
                                        <span class="color-success"><?php eh($params['message']);?></span>
                                    </div>
                            </div>
                        </div>
                        <?php
                    }

                    if (isset($params['demande_password'])) {
                    ?>
                    <div class="row" data-equalizer>
                        <div class="columns large-6 large-centered small-centered text-center">
                            <div class="panel" data-equalizer-watch>
                                <div class="row">
                                    <div class="columns large-12">
                                        <h3><?php $this->t('profil_mot_de_passe'); ?></h3>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="connexion"/>
                                            <input type="hidden" name="profil_id" value="<?php eh($this->get('profil_id')); ?>"/>
                                            <input name="password" type="password" value=""/>
                                            <input class="button success" type="submit" value="Valider" />
                                        </form>
                                        <?php
                                        if (profilHasEmail($this->get('profil_id'))) {
                                            ?>
                                                <a href="?action=send_pwd_mail&amp;profil_id=<?php eh($this->get('profil_id')); ?>">Mot de passe oublié ?</a>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    } else {
                        ?>
                        <?php
                        if ($params['pas_de_profil'] && !$params['creation']) {
                        ?>
                        <div class="row">
                            <div class="column large-12 text-center">
                                <h1>Bienvenue sur <?php echo $SHAARLO_DOMAIN; ?></h1>
                                <p>Réseau social de partage de liens hypertextes</p>
                            </div>
                        </div>

                        <div class="row" data-equalizer>
                            <div class="columns large-4 ">
                                <div class="panel" data-equalizer-watch>
                                    <div class="row">
                                        <div class="columns large-12 text-center">
                                            <h2>Nous rejoindre</h2>
                                            <br/><br/>
                                            <a href="?action=creation" class="button secondary">Créer un profil</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="columns large-4 text-center">
                                <div class="panel" data-equalizer-watch>
                                    <div class="row">
                                        <div class="columns large-12">
                                            <h2>Se connecter</h2>
                                            <form method="GET">
                                                <input type="hidden" name="action" value="connexion"/>
                                                <input name="profil_id" type="text" placeholder="pseudo" value=""/>
                                                <input class="button" type="submit" value="Charger profil" />
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="columns large-4 ">
                                <div class="panel" data-equalizer-watch>
                                    <div class="row">
                                        <div class="columns large-12 text-center">
                                            <h2>Essayer !</h2>
                                            <br/><br/>
                                            <form id="form-abonnements" method="POST">
                                                <input type="hidden" name="action" value="enregistrer_temporairement"/>
                                                <input type="hidden" name="profil_id" value=""/>
                                                <input id="button-tous" type="submit" class="button secondary" value="Accès invité" />
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                    <?php
                     if (!$params['pas_de_profil'] && !$params['creation']) {
                        ?>
                        <div class="row">
                            <div class="columns large-12">
                                <div class="panel">

                                    <div class="row">
                                    <?php
                                        /*
                                         * Bloc image de profil
                                         */
                                        if (isShaarliste()) {
                                            $imageProfilSrc = getImageProfilSrc($params['shaarliste']);
                                            if (!is_null($imageProfilSrc)) {
                                            ?>
                                                <div class="columns large-2 medium-3 small-3 text-right small-text-left">
                                                    <img class="profil-avatar" src="<?php echo $imageProfilSrc; ?>" alt="image de profil" />
                                                </div>
                                            <?php
                                            }
                                        }
                                        ?>
                                        <div class="column large-10 medium-9 small-9">
                                            <div class="row">
                                                <div class="columns large-12">
                                                    <span>
                                                        <?php eh($params['shaarliste']);?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php
                                            if (displayPoussins() && $params['is_me']) {
                                                ?>
                                                <div class="row">
                                                    <div class="columns large-12">
                                                        <a href="#" class="opacity-test-3 tiny a-reveal-poussins" data-reveal-id="div-poussins"><?php echo getPoussinsSolde(); ?> poussin(s)</a>

                                                        <div id="div-poussins" class="reveal-modal large" data-reveal aria-labelledby="Poussins" aria-hidden="true" role="dialog">
                                                            <canvas data-nb-poussins="<?php eh(getPoussinsSolde()); ?>" id="canvasPoussins"  style="border:1px solid #c3c3c3;">
                                                            Your browser does not support the HTML5 canvas tag.
                                                            </canvas>
                                                            <a class="close-reveal-modal" aria-label="Fermer">&#215;</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                                if (!isPassword()) {
                                                    ?>
                                                    <div>
                                                        <span class="opacity-test-3 tiny">(Seuls les comptes protégés par un mot de passe et ayant un shaarli peuvent recevoir des poussins)</span>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </div>

                                    </div>

                                    <hr/>
                                    <?php
                                    if ($params['is_me']) {
                                    ?>
                                    <div class="row text-center">
                                        <div class="columns large-12">
                                            <?php
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

                                            $nomImageTopGif = sprintf('img/top/top_%s.gif', $idBadge);
                                            if (is_file($nomImageTopGif)) {
                                                ?><a href="badges.php"><img src="<?php echo $nomImageTopGif; ?>" alt="top_gif"/></a><?php
                                            }
                                            ?>
                                        </div>
                                        <?php
                                        if (!empty($params['shaarlieurPositionTop']) && $params['shaarlieurPositionTop'] <= 1000) {
                                        ?>
                                        <div class="columns large-12">
                                            <?php
                                            if ($params['shaarlieurPositionTop'] == 1) {
                                                ?>Vous êtes l'utilisateur <span title="Vous êtes le <?php echo $params['shaarlieurPositionTop']; ?>°" class="top-orange">le plus fidèle</span> du site !!!<?php
                                            } elseif ($params['shaarlieurPositionTop'] <= 5) {
                                                ?>Vous êtes l'un des cinq utilisateurs <span title="Vous êtes le <?php echo $params['shaarlieurPositionTop']; ?>°" class="top-orange">les plus fidèles</span> du site !<?php
                                            } elseif ($params['shaarlieurPositionTop'] <= 10) {
                                                ?>Vous faites partie du <span title="Vous êtes le <?php echo $params['shaarlieurPositionTop']; ?>°" class="top-orange">top 10</span> des utilisateurs du site :-D<?php
                                            } elseif ($params['shaarlieurPositionTop'] <= 50) {
                                                ?>Vous faites actuellement partie du <span title="Vous êtes le <?php echo $params['shaarlieurPositionTop']; ?>°" class="top-orange">top 50</span> des utilisateurs du site :-)<?php
                                            } elseif ($params['shaarlieurPositionTop'] <= 100) {
                                                ?>Vous faites actuellement partie du <span title="Vous êtes le <?php echo $params['shaarlieurPositionTop']; ?>°" class="top-orange">top 100</span> des utilisateurs du site :-)<?php
                                            } else {
                                                ?>Vous faites actuellement partie du <span title="Vous êtes le <?php echo $params['shaarlieurPositionTop']; ?>°" class="top-orange">top 1000</span> des utilisateurs du site :-)<?php
                                            }
                                            ?>
                                        </div>
                                        <?php } ?>
                                    </div>
                                    <?php
                                    } else {
                                    ?>
                                        <div class="row">
                                            <div class="columns large-12">
                                                <a target="_blank" href="<?php eh($params['shaarli_url_ok']);?>">Shaarli</a>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="columns large-12">
                                                <a href="abonnements.php?shaarliste=<?php eh($params['shaarliste']);?>"><span class="blue-ocean"><?php eh($params['nb_abonnements'])?></span> abonnements</a>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="columns large-12">
                                                <span class="blue-ocean"><?php eh($params['nb_abonnes'])?></span> abonnés
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="columns large-12">
                                                <?php if ($params['est_abonne']) { ?>
                                                    <a href="#" onclick="javascript:lienAddAbo(this,'<?php eh($params['id_rss_ok'])?>', 'delete');return false;">Se désabonner</a>
                                                <?php } else { ?>
                                                    <a href="#" onclick="javascript:lienAddAbo(this,'<?php eh($params['id_rss_ok'])?>', 'add');return false;">Suivre</a>
                                                <?php } ?>
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
                    ?>

                    <?php
                    if (!$params['pas_de_profil'] && !$params['creation'] && $params['is_me']) {
                    ?>
                    <div class="row">
                        <div class="columns large-12 center">
                            <div class="panel">
                                <div class="row">
                                    <div class="large-3 medium-6 small-6 columns text-center">
                                        <a class="a-block" href="abonnements.php">
                                            <div class="row">
                                                <div style="min-height:150px;background:url('css/img/icon_people.png') no-repeat;background-position:center center;"></div>
                                            </div>
                                            <div class="row">
                                                <span class="blue-ocean"><?php eh($params['nb_abonnements'])?></span> abonnements
                                            </div>
                                        </a>
                                    </div>
                                    <div class="large-3 medium-6 small-6 columns text-center">
                                        <?php if(!isEnAttenteDeModeration()) { ?>
                                        <a class="a-block link-show" href="<?php if (isShaarliste()) { ?>#modifier-shaarli<?php } else { ?>#mon-shaarli<?php } ?>">
                                            <div class="row">
                                                <div style="min-height:150px;background:url('css/img/icon_shaarli.png') no-repeat;background-position:center center;"></div>
                                            </div>
                                            <div class="row">
                                                <?php if (isShaarliste()) { ?>
                                                    <?php if (!isEnAttenteDeModeration()) { ?>
                                                        <p><a class="link-show" href="#modifier-shaarli">Liaison shaarli</a></p>
                                                    <?php } else { ?>
                                                        <p><em>Shaarli en attente de modération <a href="?action=cancel" class="tiny">Annuler la demande</a></em></p>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <?php if (!isEnAttenteDeModeration()) { ?>
                                                        <p><a class="link-show" href="#mon-shaarli">Liaison shaarli</a></p>
                                                    <?php } else { ?>
                                                        <p><em>Shaarli en attente de modération <a href="?action=cancel" class="tiny">Annuler la demande</a></em></p>
                                                    <?php } ?>
                                                <?php }  ?>
                                            </div>
                                        </a>
                                        <?php } else { ?>
                                        <div class="a-block" >
                                            <div class="row">
                                                <div style="min-height:150px;background:url('css/img/icon_shaarli.png') no-repeat;background-position:center center;"></div>
                                            </div>
                                            <div class="row">
                                                <p><em>Shaarli en attente de modération <a href="?action=cancel" class="tiny">Annuler la demande</a></em></p>
                                            </div>
                                        </div>
                                        <?php }  ?>
                                    </div>
                                    <div class="large-3 medium-6 small-6 columns text-center">
                                        <a class="a-block link-show" href="#pwd">
                                            <div class="row">
                                                <div style="min-height:150px;background:url('css/img/icon_pwd.png') no-repeat;background-position:center center;"></div>
                                            </div>
                                            <div class="row">
                                                Sécurité
                                            </div>
                                        </a>
                                    </div>
                                    <div class="large-3 medium-6 small-6 columns text-center">
                                        <a class="a-block link-show" href="#options">
                                            <div class="row">
                                                <div style="min-height:150px;background:url('css/img/icon_config.png') no-repeat;background-position:center center;"></div>
                                            </div>
                                            <div class="row">
                                                Options d'affichage
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                <div class="row">
                                    <?php if (!is_null($params['id_rss'])) { ?>
                                    <div class="large-3 medium-6 small-6 columns text-center">
                                        <a class="a-block" href="index.php?q=shaarli:<?php echo $params['id_rss']; ?>">
                                            <div class="row">
                                                <div style="min-height:150px;background:url('css/img/icon_messagerie.png') no-repeat;background-position:center center;"></div>
                                            </div>
                                            <div class="row">
                                                Messagerie
                                            </div>
                                        </a>
                                    </div>
                                    <?php } ?>
                                    <?php if (isSerieux()) { ?>
                                    <div class="large-3 medium-6 small-6 columns text-center">
                                        <a class="a-block link-show" href="#filtres">
                                            <div class="row">
                                                <div style="min-height:150px;background:url('css/img/icon_filtres.png') no-repeat;background-position:center center;"></div>
                                            </div>
                                            <div class="row">
                                                Filtres des articles
                                            </div>
                                        </a>
                                    </div>
                                    <?php } ?>
                                    <div class="large-3 medium-6 small-6 columns text-center">
                                        <a class="a-block link-show" target="_blank" href="https://www.shaarli.fr/jappix-1.0.7/?r=shaarli@conference.dukgo.com">
                                            <div class="row">
                                                <div style="min-height:150px;background:url('css/img/icon_chat.png') no-repeat;background-position:center center;"></div>
                                            </div>
                                            <div class="row">
                                                Tchat en ligne
                                            </div>
                                        </a>
                                    </div>
                                    <?php if (isSerieux()) { ?>
                                    <div class="large-3 medium-6 small-6 columns text-center">
                                        <a class="a-block link-show" href="#report">
                                            <div class="row">
                                                <div style="min-height:150px;background:url('css/img/icon_report.png') no-repeat;background-position:center center;"></div>
                                            </div>
                                            <div class="row">
                                                Bug/commentaire/conseil
                                            </div>
                                        </a>
                                    </div>
                                    <?php } ?>

                                    
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <?php
                    if (false && !$params['pas_de_profil'] && !$params['creation']) {
                    ?>
                    <div class="row">
                        <div class="columns large-12 center">
                            <div class="panel">
                                <div class="row">
                                    <div class="columns large-12">
                                        <h3>Import/Export profil</h3>
                                        <p>Copier coller cette clef pour charger ce profil dans un autre navigateur</p>
                                        <form method="GET">
                                                <input type="hidden" name="action" value="connexion"/>
                                                <input name="profil_id" type="text" value="<?php echo htmlentities(getUtilisateurId());?>"/>
                                                <input class="button" type="submit" value="Charger profil" />
                                        </form>
                                        <a href="https://<?php echo $SHAARLO_DOMAIN; ?>/dashboard.php?action=connexion&amp;profil_id=<?php echo htmlentities(getUtilisateurId());?>">Url de connexion</a>
                                        <p class="astuce">Astuce : remplacez la clef par quelque chose de simple à retenir</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <?php
                    if (!$params['pas_de_profil']) {
                    ?>
                    <div class="row bloc-show" style="display:none;" id="options">
                        <div class="columns large-12 center">
                            <div class="panel">
                                <div class="row">
                                    <div class="column large-12">
                                        <h3>Paramètres</h3>
                                    </div>
                                </div>
                                <br/>
                                <div class="row">
                                    <div class="column large-12">
                                        <h5>Options fonctionnelles</h5>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher les commentaires des shaarlistes non suivis</span>
                                    </div>
                                    <div class="columns large-4">
                                            <input type="radio" <?php if(displayShaarlistesNonSuivis()) {echo ' checked="checked" ';}?> name="checkbox-shaarlistes-suivis" data-option="display_shaarlistes_non_suivis" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayShaarlistesNonSuivis()) {echo ' checked="checked" ';}?>    name="checkbox-shaarlistes-suivis" data-option="display_shaarlistes_non_suivis" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher le bloc "En ce moment sur la shaarlisphère"</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(displayBestArticle()) {echo ' checked="checked" ';}?> name="checkbox-display-best-article"  data-option="display_best_article" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayBestArticle()) {echo ' checked="checked" ';}?> name="checkbox-display-best-article" data-option="display_best_article" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <?php if ($params['shaarli_url']) { ?>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher le bloc de conversation</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(displayBlocConversation()) {echo ' checked="checked" ';}?> name="checkbox-display_bloc_conversation" data-option="display_bloc_conversation" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayBlocConversation()) {echo ' checked="checked" ';}?> name="checkbox-display_bloc_conversation" data-option="display_bloc_conversation" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <?php } ?>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher les liens sans description</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(displayEmptyDescription()) {echo ' checked="checked" ';}?> name="checkbox-display-empty-description" data-option="display_empty_description" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayEmptyDescription()) {echo ' checked="checked" ';}?> name="checkbox-display-empty-description" data-option="display_empty_description" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher uniquement les nouveaux liens du jour</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(displayOnlyNewArticles()) {echo ' checked="checked" ';}?> name="checkbox-display_only_new_articles"  data-option="display_only_new_articles" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayOnlyNewArticles()) {echo ' checked="checked" ';}?> name="checkbox-display_only_new_articles" data-option="display_only_new_articles" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher les boutons "Top du jour/hier/semaine"</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(useTopButtons()) {echo ' checked="checked" ';}?> name="checkbox-use-top-buttons"  data-option="use_top_buttons" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!useTopButtons()) {echo ' checked="checked" ';}?> name="checkbox-use-top-buttons" data-option="use_top_buttons" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher un bouton de rafraichissement sur l'article</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(useRefreshButton()) {echo ' checked="checked" ';}?> name="checkbox-use-refresh-button"  data-option="use_refresh_button" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!useRefreshButton()) {echo ' checked="checked" ';}?> name="checkbox-use-refresh-button" data-option="use_refresh_button" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher les discussions entre shaarlistes</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(displayDiscussions()) {echo ' checked="checked" ';}?> name="checkbox-display_discussions"  data-option="display_discussions" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayDiscussions()) {echo ' checked="checked" ';}?> name="checkbox-display_discussions" data-option="display_discussions" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>

                                <div class="row">
                                    <div class="columns large-8">
                                        <span>S'inscrire automatiquement aux nouveaux shaarlistes</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(isInscriptionAuto()) {echo ' checked="checked" ';}?> name="checkbox-inscription_auto"  data-option="inscription_auto" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!isInscriptionAuto()) {echo ' checked="checked" ';}?> name="checkbox-inscription_auto" data-option="inscription_auto" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Activer les poussins <span class="button microscopic alert">NEW</span></span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(displayPoussins()) {echo ' checked="checked" ';}?> name="checkbox-display_poussins"  data-option="display_poussins" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayPoussins()) {echo ' checked="checked" ';}?> name="checkbox-display_poussins" data-option="display_poussins" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <br/>
                                <div class="row">
                                    <div class="column large-12">
                                        <h5>Options graphiques</h5>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Rendre la barre de menu fixe (rafraichir la page)</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(isMenuLocked()) {echo ' checked="checked" ';}?>  name="checkbox-lock-menu" data-option="lock" class="no-margin" value="lock"/>oui
                                        <input type="radio" <?php if(!isMenuLocked()) {echo ' checked="checked" ';}?> name="checkbox-lock-menu" data-option="lock" class="no-margin" value="open"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher un lien vers le flux RSS sur la page des flux</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(displayRssButton()) {echo ' checked="checked" ';}?> name="checkbox-display_rss_button"  data-option="display_rss_button" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayRssButton()) {echo ' checked="checked" ';}?> name="checkbox-display_rss_button" data-option="display_rss_button" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                            <span>Compacter les articles longs</span>
                                    </div>
                                    <div class="columns large-4">
                                            <input type="radio" <?php if(isExtended()) {echo ' checked="checked" ';}?> name="checkbox-extend"  data-option="extend" class="no-margin" value="oui"/>oui
                                            <input type="radio" <?php if(!isExtended()) {echo ' checked="checked" ';}?> name="checkbox-extend" data-option="extend" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Activer le scroll infini</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(useScrollInfini()) {echo ' checked="checked" ';}?> name="checkbox-use_scroll_infini"  data-option="use_scroll_infini" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!useScrollInfini()) {echo ' checked="checked" ';}?> name="checkbox-use_scroll_infini" data-option="use_scroll_infini" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher les trucs du style Tipeee</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(useTipeee()) {echo ' checked="checked" ';}?> name="checkbox-use_tipeee"  data-option="use_tipeee" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!useTipeee()) {echo ' checked="checked" ';}?> name="checkbox-use_tipeee" data-option="use_tipeee" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher les images/avatars</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(displayImages()) {echo ' checked="checked" ';}?> name="checkbox-display_img"  data-option="display_img" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayImages()) {echo ' checked="checked" ';}?> name="checkbox-display_img" data-option="display_img" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher les avatars en mini</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(displayLittleImages()) {echo ' checked="checked" ';}?> name="checkbox-display_little_img"  data-option="display_little_img" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayLittleImages()) {echo ' checked="checked" ';}?> name="checkbox-display_little_img" data-option="display_little_img" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Afficher uniquement les liens non visités</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(displayOnlyUnreadArticles()) {echo ' checked="checked" ';}?> name="checkbox-display_only_unread"  data-option="display_only_unread" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!displayOnlyUnreadArticles()) {echo ' checked="checked" ';}?> name="checkbox-display_only_unread" data-option="display_only_unread" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>

                                <br/>
                                <div class="row">
                                    <div class="column large-12">
                                        <h5>Options mainstream</h5>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Utiliser <a href="https://github.com/tholman/elevator.js">elevator.js</a> dans la page des flux</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(useElevator()) {echo ' checked="checked" ';}?> name="checkbox-use-elevator"  data-option="use_elevator" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!useElevator()) {echo ' checked="checked" ';}?> name="checkbox-use-elevator" data-option="use_elevator" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <p class="no-dotsies">Utiliser <a class="no-dotsies" href="http://dotsies.org/">dotsies</a> (Y'en a qui ont essayé !)</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(useDotsies()) {echo ' checked="checked" ';}?> name="checkbox-use-dotsies"  data-option="use_dotsies" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!useDotsies()) {echo ' checked="checked" ';}?> name="checkbox-use-dotsies" data-option="use_dotsies" class="no-margin" value="non"/><span class="no-dotsies">non</span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="columns large-12 text-right">
                                        <span><form><input type="submit" class="button" value="Enregistrer"></form></span>
                                    </div>
                                </div>
                                    <!--
                                    <div class="row">
                                        <div class="columns large-8">
                                            <span>En travaux : Activer le mode river (dégroupe les liens)</span>
                                        </div>
                                        <div class="columns large-4">
                                            <input type="radio" <?php if(isModeRiver()) {echo ' checked="checked" ';}?> name="checkbox-mode_river" class="checkbox-mode_river" value="oui"/>oui
                                            <input type="radio" <?php if(!isModeRiver()) {echo ' checked="checked" ';}?> name="checkbox-mode_river" class="checkbox-mode_river" value="non"/>non
                                        </div>
                                    </div>
                                    -->
                            </div>
                        </div>
                    </div>
                    <?php } ?>


                <?php if (isSerieux() && !$params['creation']) { ?>
                <div class="row bloc-show" id="report">
                    <div class="columns large-12 center">
                        <div class="panel">
                            <div class="row">
                                <div class="columns large-12">
                                    <h3>Reporter un bug/commentaire/conseil</h3>
                                    <br/>
                                    <p>Utilisez ce champ si besoin pour toute remarque :</p>
                                    <form method="POST">
                                            <textarea name="message" rows="4"></textarea>
                                            <input type="hidden" name="action" value="report" />
                                            <input class="button" type="submit" value="Envoyer" />
                                    </form>
                                    <p>Merci à tous ceux qui ont déjà utilisé ce formulaire, votre aide est vraiment précieuse</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <?php if (isSerieux() && !$params['creation']) { ?>
                <div id="filtres" class="bloc-show">
                    <div class="row">
                        <div class="columns large-12 center">
                            <div class="panel">
                                <div class="row">
                                    <div class="columns large-12">
                                        <h3>Filtre sur les tags</h3>

                                        <form method="POST">
                                            <p>Vous pouvez afficher les articles qui contiennent UNIQUEMENT ces tags :</p>
                                            <?php
                                            if (!empty(getTags())) {
                                                ?><textarea name="tags" rows="4" placeholder="végétarisme, fun..."><?php echo htmlentities(implode(' ', getTags()));?></textarea><?php
                                            } else {
                                                ?><textarea name="tags" rows="4" placeholder="végétarisme, fun..."></textarea><?php
                                            }
                                            ?>
                                            <p>Vous pouvez filtrer les articles qui ne vous intéressent pas en fonction de leurs tags</p>
                                            <textarea name="not_allowed_tags" rows="4" placeholder="iphone, politique, science, html, css, video, wtf..."><?php echo htmlentities(implode(' ', getNotAllowedTags()));?> </textarea>
                                            <input type="hidden" name="action" value="enregistrer_tags" />

                                            <input class="button" type="submit" value="Sauvegarder" />
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="columns large-12 center">
                            <div class="panel">
                                <div class="row">
                                    <div class="columns large-12">
                                        <h3>Filtre sur les urls</h3>
                                        <form method="POST">
                                            <p>Ne jamais afficher de lien vers les sites web suivants :</p>
                                            <textarea name="not_allowed_urls" rows="4" placeholder="youtube.com..."><?php echo htmlentities(implode(' ', getNotAllowedUrls()));?> </textarea>
                                            <input type="hidden" name="action" value="enregistrer_urls" />
                                            <input class="button" type="submit" value="Sauvegarder" />
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <div class="row bloc-show" id="pwd" style="display:none;">
                    <div class="columns large-12 center">
                        <div class="panel">
                            <div class="row">
                                <div class="columns large-12">
                                    <h3>Protection par mot de passe <span class="button tiny alert">NEW</span></h3>
                                    <form method="POST" action="?">
                                        <div class="row">

                                            <div class="columns large-12">
                                                <?php if (isPassword()) { ?>
                                                    <span>Ce compte est actuellement protégé par un mot de passe.</span>
                                                    <input autocomplete="off" placeholder="Ancien mot de passe" type="password" name="old_password" value="" />
                                                <?php } ?>
                                                <input type="hidden" name="action" value="enregistrer_password"/>
                                                <input autocomplete="off" placeholder="Nouveau mot de passe" type="password" name="password" value="" />
                                                <input autocomplete="off" placeholder="Nouveau mot de passe Encore ! " type="password" name="password_verif" value="" />
                                                <input class="button" type="submit" value="Enregistrer" />
                                            </div>
                                        </div>
                                    </form>
                                    <p>La protection du compte permettra d'accéder à des fonctions avancées comme :
                                        <ul>
                                            <li>Modification de l'url de son shaarli</li>
                                            <li>Suppression du shaarli de la page d'abonnement</li>
                                            <li>Modification de son avatar</li>
                                            <li>Messagerie non accessible de l'extérieur (même si c'est assez virtuel)</li>
                                            <li>Affichage d'une page de profil publique (désactivable, avec des statistiques sur le shaarli)</li>
                                        </ul>
                                    </p>
                                    <form method="POST" action="?">
                                        <div class="row">
                                            <div class="columns large-12">
                                                <span>Email de récupération du compte : </span>
                                                <input type="hidden" name="action" value="enregistrer_email"/>
                                                <input autocomplete="off" placeholder="moi@mondomaine.tld" type="text" name="email" value="<?php eh(getEmail()); ?>" />
                                                <input class="button" type="submit" value="Enregistrer" />
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (!$params['pas_de_profil'] && !$params['creation'] && !isShaarliste()) { ?>
                    <div class="row bloc-show" id="mon-shaarli">
                        <div class="columns large-12 center">
                            <div class="panel">
                                <div class="row">
                                    <div class="columns large-12">
                                        <h3>Url de mon shaarli</h3>
                                        <p>Permet de reshaarlier les liens</p>
                                        <form method="POST">
                                            <input name="shaarli_url" type="text" value="<?php echo htmlentities($params['shaarli_url']); ?>" placeholder="http://example.com/shaarli/"/>
                                            <input type="hidden" name="action" value="mon_shaarli" />
                                            <input type="hidden" name="action" value="mon_shaarli" />
                                            <?php
                                            if (!$params['creation']) {
                                            ?>
                                             <div class="row">
                                                <div class="columns large-12">
                                                    <input class="button" type="submit" value="Sauvegarder" />
                                                </div>
                                            </div>
                                            <?php
                                            }
                                            ?>
                                        </form>
                                        <p class="astuce">Note : votre shaarli apparaitra dans la page des abonnements après modération.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if (isShaarliste() && !isEnAttenteDeModeration()) { ?>
                    <div class="row bloc-show" id="modifier-shaarli">
                        <div class="columns large-12 center">
                            <div class="panel">
                                <div class="row">
                                    <div class="columns large-12">
                                        <h3>Moi et mon shaarli</h3>
                                    </div>
                                </div>
                                <br/>
                                <div class="row">
                                    <div class="columns large-12">
                                        <h4>Mes choix de confidentialité</h4>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Apparaitre dans la liste des abonnements</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(isOnAbonnements()) {echo ' checked="checked" ';}?> name="checkbox-on_abonnements"  data-option="on_abonnements" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!isOnAbonnements()) {echo ' checked="checked" ';}?> name="checkbox-on_abonnements" data-option="on_abonnements" class="no-margin" value="non"/>non
                                    </div>
                                </div>
                                <hr class="no-margin"/>
                                <div class="row">
                                    <div class="columns large-8">
                                        <span>Apparaitre dans la River</span>
                                    </div>
                                    <div class="columns large-4">
                                        <input type="radio" <?php if(isOnRiver()) {echo ' checked="checked" ';}?> name="checkbox-on_river"  data-option="on_river" class="no-margin" value="oui"/>oui
                                        <input type="radio" <?php if(!isOnRiver()) {echo ' checked="checked" ';}?> name="checkbox-on_river" data-option="on_river" class="no-margin" value="non"/>non
                                    </div>
                                </div>

                                <br/>
                                <div class="row">
                                    <div class="columns large-12">
                                        <h4>Mes actions possibles</h4>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="columns large-8">
                                        <p><span class="fake-a" id="button-synchro-shaarli">Synchroniser mon shaarli</span> <img class="hidden" id="img-synchro-shaarli" src="img/spinner-24-24.gif"><span id="msg-synchro-shaarli"></span></p>
                                        <p><span class="fake-a" id="button-synchro-favicon">Synchroniser ma favicon</span> <img class="hidden" id="img-synchro-favicon" src="img/spinner-24-24.gif"></p>
                                    </div>
                                </div>

                                <br/>

                                <div class="row">
                                    <div class="columns large-12">
                                        <h4>Modifier l'url de mon shaarli</h4>
                                    </div>
                                    <div class="columns large-12">
                                        <form method="POST">
                                            <input name="shaarli_url" type="text" value="<?php echo htmlentities($params['shaarli_url']); ?>" placeholder="http://example.com/shaarli/"/>
                                            <input type="hidden" name="action" value="mon_shaarli" />
                                            <input type="hidden" name="action" value="mon_shaarli" />

                                            <h5>Nombre de minutes minimal entre chaque mise à jour</h5>
                                            <input name="shaarli_delai" type="number" value="<?php echo htmlentities($params['shaarli_delai']); ?>" placeholder="1"/>

                                            <div class="row">
                                                <div class="columns large-12">
                                                    <input class="button" type="submit" value="Sauvegarder" />
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
<!--
                <?php if (!$params['creation']) { ?>
                    <div class="row">
                        <div class="columns large-12 show-for-medium-up text-center">
                            <div class="panel">
                                <div class="row">
                                    <div class="columns large-12">
                                        <img src="css/img/biere.jpg" alt="biere"/>
                                        <a target="_blank" href="https://framadate.org/pr48dvawbdvw545j">Prochaine rencontre sur Paris (Shaarli AFK)</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br/>
                    <?php
                }
                ?>
-->                
                <?php if ($params['creation']) { ?>
                    <div class="row">
                        <div class="column large-12 text-center">
                            <h1>Créer un profil</h1>
                        </div>
                    </div>
                    <hr/>
                    <form method="POST" action="?">
                        <div class="row">
                            <div class="column large-12 text-center">
                                <h2>Sélectionnez les shaarlistes que vous souhaitez suivre</h2>
                            </div>
                        </div>
                        <?php
                        $this->renderListeShaarlistes($params);
                        ?>
                        <div class="row">
                            <div class="columns large-6 large-centered">
                                <div class="panel">
                                    <div class="row">
                                        <div class="columns large-12 text-center">
                                            <h3>C'est presque fini !</h3>
                                            <p>Choisissez un nom de profil</p>
                                            <input type="hidden" name="action" value="enregistrer"/>
                                            <input name="profil_id" type="text" value="<?php echo htmlentities(getUtilisateurId());?>"/>
                                            <input disabled="disabled" id="input-enregistrer-profil" class="button success" type="submit" value="Enregistrer mon profil" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php } ?>
            <?php } ?>

            <?php
            $this->renderScript($params);
            ?>
                </body>
            </html>

            <?php
        }
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
        if (displayPoussins() && $params['is_me']) {
        ?>
        <script>
        
        var canvas = document.getElementById("canvasPoussins");
        var ctx = canvas.getContext("2d");
        var poussins = [];
        function initScene() {
            poussins = [];
            var defaultColor = "#FFFF00";
            for (i=0; i < $("#canvasPoussins").attr('data-nb-poussins') ; i++) {
                creerPoussin(defaultColor);
            }
        }
        
        var x = setInterval(drawScene, 200);
        function drawScene() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = "#FF0000";
            var radius = 4;

            for (i=0; i < poussins.length ; i++) {
                if ((0.10 - Math.random()) > 0) {
                    var deplacementX = Math.round((0.5 - Math.random()) * 10);
                    var deplacementY = Math.round((0.5 - Math.random()) * 10);
                    poussins[i].positionX += deplacementX;
                    poussins[i].positionY += deplacementY;
                } else {
                    //Poussin se repose
                    if (poussins[i].breath < 1) {
                        poussins[i].breath++;
                    } else {
                        poussins[i].breath = 0;
                    }
                }

                ctx.fillStyle = poussins[i].color;
                ctx.beginPath();

                ctx.arc(poussins[i].positionX, poussins[i].positionY, radius + poussins[i].breath, 0, 2 * Math.PI, false);
                ctx.fill();
                //ctx.fillRect(poussins[i].positionX, poussins[i].positionY, 5,5);
            }

        }


        function creerPoussin(color) {
            var positionX = Math.round(Math.random() * canvas.width);
            var positionY = Math.round(Math.random() * canvas.height);
            var poussin = {positionX:positionX, positionY:positionY, color:color, breath:Math.round(Math.random()*2)};
            poussins.push(poussin);
        }
        $(document).on("click", '.a-reveal-poussins', function() {
            var canvas = document.getElementById("canvasPoussins");
            canvas.width = $(canvas).parent().width() - 10;
            canvas.height = $(canvas).parent().height() - 10;
            initScene();
        });
        </script>
        <?php
        }
        ?>
        <?php
    }
}

$dashboard = new Dashboard();
$dashboard->run();
