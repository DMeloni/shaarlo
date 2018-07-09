<?php

namespace ShaarloBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class SubscriptionController.
 */
class SubscriptionController extends AbstractController
{
    /**
     * @Route("/abonnements")
     */
    public function indexAction(Request $request)
    {
        $userOptionsUtils = $this->get('shaarlo.user_options_utils');
        $rssUtils = $this->get('shaarlo.rss_utils');

        if ($userOptionsUtils->getUtilisateurId() === '') {
            header('Location: in    dex.php');

            return null;
        }

        $userOptionsUtils->getSession();


        $apiUrl = $this->getParameter('api_url');
        $infoAboutAll = file_get_contents($apiUrl.'?do=getInfoAboutAll');
        $infoAboutAll = $rssUtils->remove_utf8_bom($infoAboutAll);
        $infoAboutAllDecoded = json_decode($infoAboutAll, true);


        if (!empty($_POST)) {
            if (isset($_POST['shaarlistes'])) {
                $abonnements = $_POST['shaarlistes'];
            } else {
                $abonnements = array();
            }
            $userOptionsUtils->majAbonnements($abonnements);
        }

        $mesAbonnements = $userOptionsUtils->getAbonnements();

        if ($request->get('shaarliste')) {
            $shaarliste = $request->get('shaarliste');
            // Récupération en bdd
            $abonnements = $userOptionsUtils->getAbonnementsByShaarlieurId($shaarliste);

            // On filtre les abonnements à afficher dans le cas des abonnements d'une personne
            foreach ($infoAboutAllDecoded['stat'] as $s => $shaarli) {
                if(!in_array($shaarli['id'], $abonnements)) {
                    unset($infoAboutAllDecoded['stat'][$s]);
                }
            }
        } else {
            //Récupération dans la session
            $abonnements = $mesAbonnements;
            $shaarliste = $userOptionsUtils->getUtilisateurId();
        }
        $nbAbonnements = count($mesAbonnements);

        $isMe = false;
        if ($shaarliste === $userOptionsUtils->getUtilisateurId()) {
            $isMe = true;
        }

        $infoAboutAllDecodedChunked = array_chunk($infoAboutAllDecoded['stat'],  4);


        return $this->render(
            '@Shaarlo/subscription.html.twig',
            array_merge($this->getGlobalTemplateParameters(),
                array('nbAbonnements' => $nbAbonnements,
                    'infoAboutAllDecodedChunked' => $infoAboutAllDecodedChunked,
                    'abonnements' => $abonnements,
                    'mes_abonnements' => $mesAbonnements,
                    'shaarliste' => $shaarliste,
                    'is_me' => $isMe,
                    'displayImages' => $userOptionsUtils->displayImages(),
                )
            )
        );
    }
}
