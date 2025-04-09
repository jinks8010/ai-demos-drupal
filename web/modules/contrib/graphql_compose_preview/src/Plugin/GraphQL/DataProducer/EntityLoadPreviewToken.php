<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\DataProducerPluginManager;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_routes\Plugin\GraphQL\DataProducer\RouteEntityExtra;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Loads an entity by UUID or ID if allowed.
 *
 * @DataProducer(
 *   id = "entity_load_preview_token",
 *   name = @Translation("Entity preview token"),
 *   description = @Translation("Load an entity preview with a token"),
 *   produces = @ContextDefinition("entity",
 *     label = @Translation("Entity"),
 *   ),
 *   consumes = {
 *     "id" = @ContextDefinition("string",
 *       label = @Translation("Entity UUID"),
 *     ),
 *     "token" = @ContextDefinition("string",
 *       label = @Translation("Entity preview token"),
 *       required = FALSE,
 *     ),
 *     "langcode" = @ContextDefinition("string",
 *       label = @Translation("Language"),
 *       required = FALSE,
 *     ),
 *   },
 * )
 */
class EntityLoadPreviewToken extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new EntityLoadByUuidOrId instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\graphql\Plugin\DataProducerPluginManager $dataProducerPluginManager
   *   Data producer manager.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    protected RequestStack $requestStack,
    protected DataProducerPluginManager $dataProducerPluginManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('plugin.manager.graphql.data_producer'),
    );
  }

  /**
   * Set the token in the context for preview.
   *
   * @param string $uuid
   *   The entity UUID.
   * @param string|null $token
   *   The preview token.
   * @param string|null $langcode
   *   The language code.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   The field context.
   *
   * @return \GraphQL\Deferred
   *   The deferred entity.
   */
  public function resolve(string $uuid, ?string $token, ?string $langcode, FieldContext $context): Deferred {

    if ($token) {
      $current = $this->requestStack->getCurrentRequest();
      $current->attributes->set('_graphql_compose_preview_token', $token);
    }

    // The plugin to base shenanigans off.
    $plugin = $this->dataProducerPluginManager->createInstance('route_entity_extra');

    // A cheeky reflection to use another plugin's protected method.
    $reflection = new \ReflectionClass(RouteEntityExtra::class);
    $property = $reflection->getProperty('langcode');
    $property->setValue($plugin, $langcode ?: $context->getContextLanguage());

    $method = $reflection->getMethod('resolvePreview');
    $method->setAccessible(TRUE);

    return $method->invoke($plugin, 'node', ['node_preview' => $uuid], $context);
  }

}
