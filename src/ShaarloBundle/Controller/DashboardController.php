<?php

namespace ShaarloBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class DashboardController extends AbstractController
{
    public function indexAction(Request $request)
    {
        $userOptionsUtils = $this->get('shaarlo.user_options_utils');
        $shaarloRssUtils = $this->get('shaarlo.rss_utils');

        global $SHAARLO_DOMAIN;
        // Accès invité
        if ('enregistrer_temporairement' ===  $this->post('action')) {
            // Connexion invitée
            getSession('', true);

            return $this->redirectToRoute('shaarlo_river');
        }

        if ($userOptionsUtils->getUtilisateurId() === '') {
            return $this->redirectToRoute('shaarlo_river');
        }


        $pasDeProfil = false;
        if (empty($userOptionsUtils->getAbonnements())) {
            $pasDeProfil = true;
        }

        $shaarliste = $userOptionsUtils->getUtilisateurId();
        if ($request->get('shaarliste')) {
            $shaarliste = $request->get('shaarliste');
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

        if (true === $pasDeProfil) {
            return $this->redirectToRoute('shaarlo_subscription');
        }

        if ($request->get('action')) {
            // Connexion
            if ('connexion' ===  $request->get('action')) {
                $password = null;
                if ($this->post('password')) {
                    $password = $this->post('password');
                }
                $session = getSession($request->get('profil_id'), true, $password);
                if ($session !== 401) {
                    setcookie('shaarlieur', $request->get('profil_id'), time()+31536000, $SHAARLO_DOMAIN);
                    header('Location: index.php');
                    return;
                }

                $params['message'] = "Ce compte est protégé par un mot de passe";
                $params['demande_password'] = "Ce compte est protégé par un mot de passe";
            }

            // Création d'un nouveau profil
            if ('creation' ===  $request->get('action')) {
                $creation = true;
            }
            // Message d'erreur apres redirection
            if ($request->get('erreur') && $request->get('erreur') == 'profil_exists') {
                $params['message'] = "Ce profil existe deja, merci de choisir un autre pseudo";
            }

            // Annulation demande de modération
            if ('cancel' === $request->get('action') && $isMe) {
                $userOptionsUtils->cancelShaarliUrl();

                return $this->redirectToRoute('shaarlo_dashboard');
            }

            // Envoie du mail à l'utilisateur
            if ($pasDeProfil && 'send_pwd_mail' === $request->get('action') && !empty($request->get('profil_id'))) {
                if ($userOptionsUtils->profilHasEmail($request->get('profil_id'))) {
                    // Envoie du mail
                    $email = $userOptionsUtils->profilGetEmail($request->get('profil_id'));
                    $nouveauPassword = uniqid();
                    $retourEnvoi = envoieMailRecuperation($email, $request->get('profil_id'), $nouveauPassword);
                    if ($retourEnvoi === true) {
                        $emailObfusque = obfusqueEmail($email);
                        updateNewPassword($request->get('profil_id'), $nouveauPassword);
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
        $params['isPassword'] = $userOptionsUtils->isPassword();

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
        $params['not_urls_list'] = implode(' ', $userOptionsUtils->getNotAllowedUrls());
        dump($params);
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


        </script>
        <?php
    }
}