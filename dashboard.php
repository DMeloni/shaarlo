<?php

require_once 'bootstrap.php';

use Shaarlo\Controller\DashboardController;

$dashboard = new DashboardController();
$dashboard->run();
