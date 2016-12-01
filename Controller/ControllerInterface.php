<?php

namespace Shaarlo\Controller;

interface ControllerInterface
{
    /**
     * Execute controller logic.
     */
    public function run();

    /**
     * Display the page.
     *
     * @param array $params
     */
    public function render($params=array());

    /**
     * Display javascript content.
     *
     * @param array $params
     */
    public static function renderScript($params = array());
}