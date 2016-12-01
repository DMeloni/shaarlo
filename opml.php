<?php

require_once 'bootstrap.php';

use Shaarlo\Controller\OpmlController;

// Create the controller and run it.
$controller = new OpmlController();
$controller->run();
