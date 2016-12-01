<?php

namespace ShaarloBundle\Lang;

interface LangInterface
{
    /*
     * Translate the message.
     *
     * @param string $code
     *
     * @return string
     */
    public function trans($code);
}

