<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
$autoloader = require_once 'autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();
\Drupal::setContainer($container);
$container->get('request_stack')->push($request);
$controller = new \Drupal\visualizador_programas\Controller\VisualizadorProgramasController();
try {
  $response = $controller->getData();
  echo $response->getContent();
} catch (\Exception $e) {
  echo "Exception: " . $e->getMessage() . "\n";
} catch (\Error $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
