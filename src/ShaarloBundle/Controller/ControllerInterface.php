<?php

namespace ShaarloBundle\Controller;

interface ControllerInterface
{
    /**
     * Execute controller logic.
     */
    public function run();

    /**
     * Display javascript content.
     *
     * @param array $params
     */
    public static function renderScript($params = array());
}