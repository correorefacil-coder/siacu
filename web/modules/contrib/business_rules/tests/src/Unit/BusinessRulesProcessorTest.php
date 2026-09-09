<?php

namespace Drupal\Tests\business_rules\Unit;

use Drupal\business_rules\Entity\BusinessRule;
use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\Plugin\BusinessRulesActionManager;
use Drupal\business_rules\Plugin\BusinessRulesConditionManager;
use Drupal\business_rules\Plugin\BusinessRulesVariable\CustomValueVariable;
use Drupal\business_rules\Plugin\BusinessRulesVariableManager;
use Drupal\business_rules\Util\BusinessRulesProcessor;
use Drupal\business_rules\Util\BusinessRulesUtil;
use Drupal\business_rules\Util\Flowchart\Flowchart;
use Drupal\business_rules\VariablesSet;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\business_rules\Util\BusinessRulesProcessor
 *
 * @group business_rules
 */
class BusinessRulesProcessorTest extends UnitTestCase {

  /**
   * The entity types plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The Business rules Variable plugin manager.
   *
   * @var \Drupal\business_rules\Plugin\BusinessRulesVariableManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $businessRulesVariableManager;

  /**
   * The Business rules Processor.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesProcessor|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $businessRulesProcessor;

  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    // The following services are required by the business_rules.util service.

    $container->set('entity_field.manager', $this->createMock(EntityFieldManagerInterface::class));
    $container->set('plugin.manager.field.field_type', $this->createMock(FieldTypePluginManagerInterface::class));
    $container->set('entity_type.bundle.info', $this->createMock(EntityTypeBundleInfoInterface::class));

    // Service config.factory must return an immutable config instance.
    $config = $this->createMock(ImmutableConfig::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->expects($this->any())
      ->method('get')
      ->willReturn($config);
    $container->set('config.factory', $configFactory);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $this->businessRulesVariableManager = $this->createMock(BusinessRulesVariableManager::class);
    $container->set('plugin.manager.business_rules.variable', $this->businessRulesVariableManager);

    // Service request_stack must return the current request.
    $request = $this->createMock(Request::class);
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);
    $container->set('request_stack', $requestStack);
    $container->set('logger.factory', $this->createMock(LoggerChannelFactory::class));
    $container->set('keyvalue.expirable', $this->createMock(KeyValueExpirableFactory::class));
    $container->set('business_rules.flowchart', $this->createMock(Flowchart::class));
    $container->set('module_handler', $this->createMock(ModuleHandlerInterface::class));
    $container->set('token', $this->createMock(Token::class));

    // Instantiate real business_rules.util service class.
    $container->set('business_rules.util', new BusinessRulesUtil($container));

    // The following services are required by the business_rules.processor
    // service.
    $container->set('uuid', $this->createMock(UuidInterface::class));
    $container->set('event_dispatcher', $this->createMock(EventDispatcherInterface::class));
    $container->set('config.storage', $this->createMock(StorageInterface::class));
    $container->set('entity_type.repository', $this->createMock(EntityTypeRepositoryInterface::class));
    $container->set('plugin.manager.business_rules.action', $this->createMock(BusinessRulesActionManager::class));
    $container->set('plugin.manager.business_rules.condition', $this->createMock(BusinessRulesConditionManager::class));
    $container->set('messenger', $this->createMock(MessengerInterface::class));

    // Instantiate mocked business_rules.processor service class due to the fact
    // that __destruct() method causes PHP fatal error during the test
    // exectution.
    $this->businessRulesProcessor = $this->getMockBuilder(BusinessRulesProcessor::class)
      ->enableOriginalConstructor()
      ->setConstructorArgs([$container])
      ->setMethods(['__destruct'])
      ->getMock();
    $container->set('business_rules.processor', $this->businessRulesProcessor);

    \Drupal::setContainer($container);
  }

  /**
   * @covers ::evaluateVariable
   */
  public function testEvaluateVariableMultipleEventsWithinSingleRequest() {

    $entityStorage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($entityStorage);

    // Create a variable to test against.
    $variable = new Variable([
      'id' => 'test_variable',
      'type' => 'custom_value_variable',
      'settings' => [
        'value' => 'test_value',
      ],
    ]);

    // Ensure that variable is being loaded by the entity storage.
    $entityStorage->expects($this->any())
      ->method('load')
      ->willReturnMap([
        ['test_variable', $variable],
    ]);

    // Ensure that variable definition is available.
    $this->businessRulesVariableManager->expects($this->any())
      ->method('getDefinition')
      ->willReturnMap([
        ['custom_value_variable', TRUE, ['class' => CustomValueVariable::class, 'id' => 'custom_value_variable']],
    ]);

    // Service business_rules.processor requires a business rule to operate.
    $rule = $this->createMock(BusinessRule::class);
    $this->businessRulesProcessor->ruleBeingExecuted = $rule;

    // Test against the simpliest event.
    $dummy = new \stdClass();

    $eventA = new BusinessRulesEvent($dummy, [
      'entity_type_id' => '',
      'bundle' => NULL,
      'entity' => NULL,
      'entity_unchanged' => NULL,
      'loop_control' => 'event_A',
      'variables' => new VariablesSet(),
    ]);
    $this->assertNotNull($this->businessRulesProcessor->evaluateVariable($variable, $eventA));

    $eventB = new BusinessRulesEvent($dummy, [
      'entity_type_id' => '',
      'bundle' => NULL,
      'entity' => NULL,
      'entity_unchanged' => NULL,
      'loop_control' => 'event_B',
      'variables' => new VariablesSet(),
    ]);
    $this->assertNotNull($this->businessRulesProcessor->evaluateVariable($variable, $eventB));
  }

}
