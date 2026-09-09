<?php

use Symfony\Contracts\EventDispatcher\Event;
/**
 * @file
 * Business Rules module integration with Drush 8 and earlier.
 */

use Drupal\business_rules\Events\BusinessRulesDrushEvent;

/**
 * Implements hook_drush_init().
 */
function business_rules_drush_init() {
  if (\Drupal::hasService('event_dispatcher')) {
    \Drupal::service('event_dispatcher')->dispatch(new Event(), BusinessRulesDrushEvent::DRUSHINIT);
  }
}
