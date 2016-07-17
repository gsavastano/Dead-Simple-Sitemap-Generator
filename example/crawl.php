#!/usr/bin/env php

<?php

date_default_timezone_set('Europe/Rome');
ini_set('memory_limit', '2048M');

require_once __DIR__.'/../src/Dssg.php';

use gsavastano\Dssg\Dssg;

$test = new Dssg;
$test->startCrawl();