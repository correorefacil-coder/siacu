<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\business_rules\VariablesSet;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Set multiple fields value to any entity.
 *
 * @package Drupal\business_rule\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "set_multiple_fields_value",
 *   label = @Translation("Set multiple fields value"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Set multiple fields value to any entity."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = FALSE,
 * )
 */
class SetMultipleFieldsValue extends BusinessRulesActionPlugin {

  use StringTranslationTrait;

  // @codingStandardsIgnoreStart
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration = [], $plugin_id = 'set_multiple_fields_value', $plugin_definition = []) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }
  // @codingStandardsIgnoreEnd

  /**
   * Add field and value.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function fieldValueSave(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\business_rules\Entity\Action $action */
    $field = $form_state->getValue('entity_field');
    $value = $form_state->getValue('field_value');
    $keep_old_value = $form_state->getValue('keep_old_values');

    $action              = $form_state->get('business_rules_item');
    $field_value         = is_array($action->getSettings('fields_values')) ? $action->getSettings('fields_values') : [];
    $field_value[$field] = [
      'entity_field' => $field,
      'field_value'  => $value,
      'keep_old_values' => $keep_old_value,
    ];
    $action->setSetting('fields_values', $field_value);
    $action->save();

    \Drupal::request()->query->remove('destination');
    $form_state->setRedirect('entity.business_rules_action.edit_form', ['business_rules_action' => $action->id()], ['fragment' => 'field_value-' . $field]);
  }

  /**
   * Validate handler for field and value.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function formValidate(array $form, FormStateInterface $form_state) {
    if (!$form_state->getValue('entity_field')) {
      $form_state->setErrorByName('field', t('Select an field to add.'));
    }
    if (!$form_state->getValue('field_value')) {
      $form_state->setErrorByName('field_value', t('Fill a field value to add.'));
    }
  }

  /**
   * Remove one field/value's setting.
   *
   * @param string $action
   *   The action id.
   * @param string $field
   *   The field id.
   * @param string $method
   *   The method ajax|nojs.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The AjaxResponse or RedirectResponse object.
   */
  public static function removeFieldValue($action, $field, $method) {
    $action        = Action::load($action);
    $fields_values = $action->getSettings('fields_values');
    unset($fields_values[$field]);
    $action->setSetting('fields_values', $fields_values);
    $action->save();

    if ($method == 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand('#field_value-' . $field));

      return $response;
    }
    else {
      $url = new Url('entity.business_rules_action.edit_form', ['business_rules_action' => $action->id()]);

      return new RedirectResponse($url->toString());
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $form['#attached']['library'][] = 'business_rules/style';
    $settings = [];

    $settings['entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#required' => TRUE,
      '#default_value' => $item->getSettings('entity_id'),
      '#description' => $this->t('The entity ID to be updated. You can use tokens on this field.'),
    ];

    if (!$item->isNew()) {

      $settings['field_value_title'] = [
        '#type' => 'item',
        '#title' => $this->t('Fields and values for the entity'),
      ];

      $settings['field_value'] = [
        '#type' => 'table',
        '#sticky' => TRUE,
        '#header' => [
          $this->t('Field'),
          $this->t('Value'),
          $this->t('Keep old values'),
          $this->t('Operations'),
        ],
        '#empty' => $this->t('There are currently no values. Add one by selecting an option below.'),
      ];

      $fields_values = $item->getSettings('fields_values');
      $fields = $this->util->getBundleFields($item->getTargetEntityType(), $item->getTargetBundle());

      $settings['fields_values'] = [
        '#type' => 'value',
        '#value' => $fields_values,
      ];

      if (is_array($fields_values)) {
        foreach ($fields_values as $key => $value) {

          $settings['field_value'][$key]['#attributes'] = ['id' => 'field_value-' . $key];

          $settings['field_value'][$key]['entity_field'] = [
            'data' => [
              'label' => [
                '#plain_text' => $fields[$value['entity_field']],
              ],
            ],
          ];

          $settings['field_value'][$key]['field_value'] = [
            'data' => [
              'label' => [
                '#markup' => nl2br($value['field_value']),
              ],
            ],
          ];

          $settings['field_value'][$key]['keep_old_values'] = [
            'data' => [
              'label' => [
                '#markup' => nl2br(($value['keep_old_values'] == TRUE) ? 'YES' : 'NO'),
              ],
            ],
          ];

          $links = [];

          $links['delete'] = [
            'title' => $this->t('Remove'),
            'url' => Url::fromRoute('business_rules.plugins.action.fill_entity_variable_fields.remove_field', [
              'action' => $item->id(),
              'field' => $key,
              'method' => 'nojs',
            ], [
              'attributes' => ['class' => ['use-ajax']],
            ]),
          ];
          $settings['field_value'][$key]['operations'] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];

        }
      }

      $settings['field_value']['new'] = [
        '#tree' => FALSE,
      ];

      $settings['field_value']['new']['entity_field'] = [
        'data' => [
          'entity_field' => [
            '#type' => 'select',
            '#title' => $this->t('Field'),
            '#title_display' => 'invisible',
            '#options' => $fields,
            '#empty_option' => $this->t('Select the field'),
          ],
        ],
        '#prefix' => '<div class="field-value-new">',
        '#suffix' => '</div>',
      ];

      $settings['field_value']['new']['field_value'] = [
        '#type' => 'textarea',
        '#rows' => 1,
        '#description' => $this->t('The value to be set on the field. For a multi-valor field (cardinality > 1) type one value per line starting by pipeline (|) as the example:
          <br>|Value 1
          <br>|Value 2
          <br>|Value 3
          <br>To keep old values of this field check the "Keep old values" checkbox'),
      ];

      $settings['field_value']['new']['keep_old_values'] = [
        '#type' => 'checkbox',
      ];

      $settings['field_value']['new']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
        '#validate' => [get_class($this) . '::formValidate'],
        '#submit' => [get_class($this) . '::fieldValueSave'],
      ];

      $form_state->set('business_rules_item', $item);
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {
    // Unset the values from the add new line.
    unset($settings['field_value']);
    unset($settings['keep_old_values']);
    unset($settings['entity_field']);
    unset($settings['field_value_title']);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {

    $event_variables = $event->getArgument('variables');
    $entity_id = $this->processVariables($action->getSettings('entity_id'), $event_variables);
    $result = [];

    $entity_type = $action->getTargetEntityType();
    $bundle = $action->getTargetBundle();

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
     */
    $entity_manager = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entity = $entity_manager->load($entity_id);

    if ($entity && $entity->bundle() == $bundle) {

      $fields_values = $action->getSettings('fields_values');

      if (is_array($fields_values)) {
        foreach ($fields_values as $field_value) {

          $cardinality = $entity->get($field_value['entity_field'])
            ->getFieldDefinition()
            ->getFieldStorageDefinition()
            ->getCardinality();
          $field_old_values = $entity->get($field_value['entity_field'])->getValue();

          if ($cardinality === 1) {
            // Single value field.
            $value = $this->processVariables($field_value['field_value'], $event_variables);
          }
          else {
            // Multiple value field.
            $arr = explode(chr(10) . '|', $field_value['field_value']);
            if (substr($arr[0], 0, 1) == '|') {
              $arr[0] = substr($arr[0], 1, strlen($arr[0]) - 1);
            }
            $keep_old_values = $this->processVariables($field_value['keep_old_values'], $event_variables);

            // If multi-value is enabled.
            if ($keep_old_values == TRUE && !empty($field_old_values)) {

              // Get previous values from the field.
              foreach ($field_old_values as $value) {
                foreach ($value as $key => $value_raw) {
                  if ($key == "target_id" || $key == "value") {
                    // Push previous values to be saved with the new values.
                    array_push($arr, $value_raw);
                  }
                }
              }

            }

            foreach ($arr as $key => $value) {
              if (substr($value, strlen($value) - 1, 1) == "\r") {
                $arr[$key] = substr($value, 0, strlen($value) - 1);
              }
              $arr[$key . '000000'] = $this->processVariables($arr[$key], $event_variables);
              unset($arr[$key]);
            }

            // Put all values at the array root.
            foreach ($arr as $key => $item) {
              if (is_array($item)) {
                unset($arr[$key]);
                foreach ($item as $new_key => $new_item) {
                  $arr[$key + $new_key] = $new_item;
                }
              }
            }
            ksort($arr);

            // Remove empty values.
            if (is_array($arr) && count($arr)) {
              foreach ($arr as $key => $item) {
                if (empty($item) || is_null($item) || (is_string($item) && strlen(trim($item)) == 0)) {
                  $arr[$key] = NULL;
                }
              }
            }

            $value = $arr;
          }

          if (empty($value) || is_null($value) || (is_string($value) && strlen(trim($value)) === 0)) {
            $value = NULL;
          }

          $entity->set($field_value['entity_field'], $value);
          $entity->save();

          $result[$field_value['entity_field']] = [
            '#type'   => 'markup',
            '#markup' => $this->t('Entity id: %entity_id with bundle: %bundle - field: %field filled with value: %value.', [
              '%entity_id' => $entity_id,
              '%bundle' => $bundle,
              '%field'    => $field_value['entity_field'],
              '%value'    => is_array($value) ? implode(',', $value) : $value,
            ]),
          ];

        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function processVariables($content, VariablesSet $variables) {

    if ($variables->count()) {
      foreach ($variables->getVariables() as $variable) {
        if (is_string($variable->getValue()) || is_numeric($variable->getValue())) {
          $content = str_replace('{{' . $variable->getId() . '}}', $variable->getValue(), $content);
        }
        elseif (is_array($variable->getValue())) {
          if (preg_match_all(self::VARIABLE_REGEX, $content)) {
            if ($content == '{{' . $variable->getId() . '}}') {
              $content = $variable->getValue();
            }
          }
        }
      }
    }

    return $content;
  }

}
