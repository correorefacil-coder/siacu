<?php

namespace Drupal\miniorange_saml\Form;

use \Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements dependency injection for miniorange_saml forms.
 */
class MiniorangeSAMLFormBase extends FormBase
{

  /**
   * The base URL of the Drupal installation.
   *
   * @var string
   */
    protected string $base_url;

  /**
   * A config object fetching configuration in config table.
   *
   * @var \Drupal\Core\Config\Config
   */
    protected Config $config;

  /**
   * A config object for storing, updating, and deleting stored configuration in config table.
   *
   * @var \Drupal\Core\Config\Config
   */
    protected Config $config_factory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
    protected $messenger;

  /**
   * The logger factory.
   *
   * @var \Psr\Log\LoggerInterface
   */
    protected LoggerChannelInterface $logger;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
    public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, LoggerChannelFactoryInterface $logger_factory)
    {
        $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
        $this->base_url = $base_url;
        $this->config = $config_factory->getEditable('miniorange_saml.settings');
        $this->config_factory = $config_factory->getEditable('miniorange_saml.settings');
        $this->messenger = $messenger;
        $this->logger = $logger_factory->get('miniorange_saml');
    }

  /**
   * {@inheritdoc}
   */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('config.factory'),
            $container->get('messenger'),
            $container->get('logger.factory'),
        );
    }

  /**
   * {@inheritdoc}
   */
    public function getFormId()
    {
      // @todo Implement getFormId() method.
    }

  /**
   * {@inheritdoc}
   */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
      // @todo Implement buildForm() method.
    }

  /**
   * {@inheritdoc}
   */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
      // @todo Implement submitForm() method.
    }
}
