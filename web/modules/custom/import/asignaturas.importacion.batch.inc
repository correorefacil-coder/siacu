<?php

function disc_migrate($type, $options = array(), &$context)
{

  // Do heavy coding here...
  $message = 'Ready spaghetti...';

  switch ($type) {
    case 'courses':
      $message = 'migrating courses...';
      $context['results'][] = $options;
      break;
    default:
      $message = 'migrating somethine else...';
      break;
  }

  $context['message'] = $message;
}

function disc_migrate_finished_callback($success, $results, $operations)
{
  // The 'success' parameter means no fatal PHP errors were detected. All
  // other error management should be handled using 'results'.
  if ($success) {
    $message = \Drupal::translation()->formatPlural(
      count($results),
      'One post processed.',
      '@count posts processed.'
    );
  } else {
    $message = t('Finished with an error.');
  }
  \Drupal::messenger()->addMessage($message);
  //$_SESSION['disc_migrate_batch_results'] = $results;
}
