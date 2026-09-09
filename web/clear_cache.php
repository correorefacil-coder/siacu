<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
$autoloader = require_once 'autoload.php';
$request = Request::createFromGlobals();
require_once 'core/includes/utility.inc';
drupal_rebuild($autoloader, $request);
echo "Cache cleared!\n";
