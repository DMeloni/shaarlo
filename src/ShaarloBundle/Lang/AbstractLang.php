<?php

namespace ShaarloBundle\Lang;

abstract class AbstractLang implements LangInterface
{
    public $messages = array();

    /*
     * Retourne le message demandé
     *
     * @param string $code : le code du message
     *
     * @return
     */
    public function trans($code)
    {
        $messages = $this->messages;
        if (isset($messages[$code])) {
            return $messages[$code];
        }

        return '';
    }
}

