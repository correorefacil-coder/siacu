<?php

namespace Drupal\views_data_export_phpspreadsheet\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\views_data_export\Plugin\views\style\DataExport;

/**
 * A style plugin for data export views.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "xls_data_export",
 *   title = @Translation("Xlsx export"),
 *   help = @Translation("Configurable row output for excel exports."),
 *   display_types = {"data"}
 * )
 */
class XlsxExport extends DataExport {

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $serializer, array $serializer_formats, array $serializer_format_providers) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer, $serializer_formats, $serializer_format_providers);
    $this->formats[] = 'xls';
    $this->formatProviders['xls'] = 'serialization';
    $this->formats[] = 'xlsx';
    $this->formatProviders['xlsx'] = 'serialization';
    $this->formats[] = 'ods';
    $this->formatProviders['ods'] = 'serialization';
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    // XLS color options.
    if ($this->displayHandler) {
      $fields = $this->displayHandler->getOption('fields');
      foreach ($fields as $field => $field_setting) {
        $options['xls_settings']['contains']['color'][$field] = ['default' => NULL];
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'style_options':
        if (empty($form['xls_settings'])) {
          $form['xls_settings'] = [
            '#type' => 'details',
            '#title' => $this->t('xlsx Settings'),
          ];
        }
        $form['xls_settings']['#states']['visible'][':input[name="style_options[formats]"]'] = [
          ['value' => 'xls'],
          ['value' => 'xlsx'],
          ['value' => 'ods'],
          ['value' => 'slk'],
          ['value' => 'gnumeric'],
        ];
        unset($form['xls_settings']['xls_format']);
        $format_options = $this->getFormatOptions();
        if (in_array('xls', $format_options)) {

          $xls_options = $this->options['xls_settings'] ?? NULL;
          // Add header && footer because rest_export doesn't support.
          $form['xls_settings']['header'] = [
            '#title' => $this->t('Print header'),
            '#type' => 'text_format',
            '#rows' => 4,
            '#editor' => FALSE,
            '#format' => filter_default_format(),
            '#default_value' => !empty($xls_options["header"]["value"]) ? $xls_options['header'] : NULL,
          ];
          $form['xls_settings']['footer'] = [
            '#title' => $this->t('Print footer'),
            '#type' => 'text_format',
            '#rows' => 4,
            '#editor' => FALSE,
            '#format' => filter_default_format(),
            '#default_value' => !empty($xls_options["footer"]["value"]) ? $xls_options['footer'] : NULL,
          ];
          $form['xls_settings']['row_color'] = [
            '#title' => $this->t('Row Color'),
            '#type' => 'textfield',
            '#description' => $this->t('Apply color only to row number, separated by ,'),
            '#default_value' => $xls_options['row_color'] ?? NULL,
          ];
          $form['xls_settings']['color'] = [
            '#title' => $this->t('Color column'),
            '#type' => 'details',
            '#tree' => TRUE,
            '#states' => [
              'visible' => [
                ':input[name="style_options[formats]"]' => [
                  ['value' => 'xls'],
                  ['value' => 'xlsx'],
                  ['value' => 'ods'],
                  ['value' => 'slk'],
                ],
              ],
            ],
          ];

          $xls_options = $this->options['xls_settings'];
          $fields = $this->displayHandler->getOption('fields');
          foreach ($fields as $field => $field_setting) {
            if (!$field_setting['exclude']) {
              $form['xls_settings']['color'][$field] = [
                '#type' => 'color',
                '#title' => !empty($field_setting['label']) ? $field_setting['label'] : $field,
                '#default_value' => !empty($xls_options['color'][$field]) ? $xls_options['color'][$field] : NULL,
              ];
            }
          }
        }
        break;
    }
  }

}
