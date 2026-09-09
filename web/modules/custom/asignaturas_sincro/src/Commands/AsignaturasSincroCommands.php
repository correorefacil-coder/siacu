<?php

namespace Drupal\asignaturas_sincro\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;
use Drupal\asignaturas_sincro\Controller\AsignaturasSincroController;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class AsignaturasSincroCommands extends DrushCommands {

  /**
   * Command description here.
   *
   *
   * @command asignaturas_sincro:sincronizacionBanner
   * @aliases sincronizacionBanner
   */
  public function sincronizacionBanner() {
    $controller = new AsignaturasSincroController;
    $controller->sincronizarAsignaturasBanner();
    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * Command description here.
   *
   *
   * @command asignaturas_sincro:sincronizacionGAP
   * @aliases sincronizacionGAP
   */
  public function sincronizacionGAP()
  {
    $this->logger()->success(dt('Achievement unlocked.'));
  }

}
