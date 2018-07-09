<?php

namespace ShaarloBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;

class AboutController extends AbstractController
{
    /**
     * @Route("/about")
     */
    public function indexAction()
    {
        return $this->render(
            '@Shaarlo/about.html.twig',
            array_merge($this->getGlobalTemplateParameters(),
                [
                    'content' => 'dd'
                ]
            )
        );
    }
}
