#!/usr/bin/env php

<?php
//error_reporting(0);
date_default_timezone_set('Europe/Rome');
ini_set('memory_limit', '2048M');

require_once __DIR__.'/../src/Dssg.php';

use gsavastano\Dssg\Dssg;

$crawler = new Dssg;

//optional if passing params by CLI
$crawler->loadConfig(__DIR__.'/config.json');

$crawler->startCrawl();