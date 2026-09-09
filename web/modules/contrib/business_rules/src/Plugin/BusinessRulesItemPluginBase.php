<?php

namespace Drupal\business_rules\Plugin;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\VariableObject;
use Drupal\business_rules\VariablesSet;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base Class for Business rules plugins.
 *
 * @package Drupal\business_rules\Plugin
 */
abstract class BusinessRulesItemPluginBase extends PluginBase implements BusinessRulesItemPluginInterface {

  /**
   * The business rules processor.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesProcessor
   */
  protected $processor;

  /**
   * The business rules util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->processor = \Drupal::service('business_rules.processor');
    $this->util      = \Drupal::service('business_rules.util');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->pluginDefinition['group'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item);

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(ItemInterface $item) {
    return $item->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getEditUrl(ItemInterface $item) {
    return $item->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function pregMatch($string) {

    if (is_string($string)) {
      preg_match_all(self::VARIABLE_REGEX, $string, $variables);

      return $variables[1];
    }
    else {
      throw new \Exception('Only strings are acceptable to pregMatch variables');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables(ItemInterface $item) {
    $variableSet = new VariablesSet();

    $settings = $item->getSettings();

    $this->collectVariables($variableSet, $settings);

    return $variableSet;
  }

  /**
   * The helper function to recursively find the settings and prepare VariableSet.
   *
   * @param \Drupal\business_rules\VariablesSet $variable_set
   *   The VariableSet that will be filled with found variables.
   * @param array $settings
   *   The item settings.
   */
  private function collectVariables(VariablesSet $variable_set, array $settings) {
    foreach ($settings as $setting) {
      if (is_string($setting)) {
        $variables = $this->pregMatch($setting);

        if (count($variables)) {
          foreach ($variables as $variable_id) {
            if (stristr($variable_id, '->') && !stristr($variable_id, '[')) {
              $arr_temp    = explode('->', $variable_id);
              $variable_id = $arr_temp[0];
              $variable    = Variable::load($variable_id);
              unset($arr_temp[0]);
            }
            elseif (stristr($variable_id, '[')) {
              $arr_temp    = explode('[', $variable_id);
              $variable_id = $arr_temp[0];
              $variable    = Variable::load($variable_id);
              unset($arr_temp[0]);
            }
            else {
              $variable = Variable::load($variable_id);
            }
            if (!empty($variable)) {
              $varObject = new VariableObject($variable_id, NULL, $variable->getType());
              $variable_set->append($varObject);
            }
          }
        }
      }
      elseif (is_array($setting)) {
        $this->collectVariables($variable_set, $setting);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processVariables($content, VariablesSet $event_variables) {
    /** @var \Drupal\business_rules\VariableObject $variable */
    $content = ($content === NULL) ? '' : $content;
    if ($event_variables->count()) {
      foreach ($event_variables->getVariables() as $variable) {
        if (is_string($variable->getValue()) || is_numeric($variable->getValue())) {
          $content = str_replace('{{' . $variable->getId() . '}}', $variable->getValue(), $content);
        }
        elseif (is_array($variable->getValue())) {
          // Handle the case then content is an empty array. It will cause
          // warnings if it's not a string for the preg_match_all().
          if (is_array($content) && empty($content)) {
            $content = '';
          }

          if (preg_match_all(self::VARIABLE_REGEX, $content)) {
            if ($content == '{{' . $variable->getId() . '}}') {
              $content = $variable->getValue();
            }
            elseif (stristr($content, '{{' . $variable->getId() . '}}')) {
              $value   = implode(chr(10), $variable->getValue());
              $content = str_replace('{{' . $variable->getId() . '}}', $value, $content);
            }
          }
        }
        elseif (empty($variable->getValue())) {
          $content = str_replace('{{' . $variable->getId() . '}}', '', $content);
        }
      }
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function processTokens(ItemInterface &$item, BusinessRulesEvent $event) {
    $settings = $item->getSettings();

    // Get the context entity to pass to token processor.
    try {
      $entity_type = $event->getArgument('entity_type_id');
      $entity      = $event->getArgument('entity');
      $context     = [$entity_type => $entity];
    }
    catch (\InvalidArgumentException $e) {
      $context = [];
    }

    foreach ($settings as $key => $setting) {
      if (is_string($setting)) {
        $variables = $event->getArgument('variables');
        $setting = $this->processVariables($setting, $variables);

        $processed = $setting;
        // Do the token replace only if $setting is not an array because it will
        // cause warnings.
        if (!is_array($setting)) {
          $processed = $this->util->token->replace($setting, $context, ['clear' => TRUE]);
        }
        $settings[$key] = $processed;
      }
      elseif (is_array($setting)) {
        $this->processTokenArraySetting($settings[$key], $context, $event);
      }
    }

    foreach ($settings as $key => $setting) {
      $item->setSetting($key, $setting);
    }
  }

  /**
   * Helper function to process tokens if the setting is an array.
   *
   * @param array $setting
   *   The setting array.
   * @param array $context
   *   The context to replace the tokens.
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The Business Rules event.
   */
  private function processTokenArraySetting(array &$setting, array $context, BusinessRulesEvent $event) {
    if (count($setting)) {
      foreach ($setting as $key => $value) {
        if (is_string($value)) {
          $variables = $event->getArgument('variables');
          $value = $this->processVariables($setting[$key], $variables);
          $setting[$key] = $this->util->token->replace($value, $context, ['clear' => TRUE]);
        }
        elseif (is_array($value)) {
          $this->processTokenArraySetting($setting[$key], $context, $event);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

}
