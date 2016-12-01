<?php

namespace Shaarlo\Lang;

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

