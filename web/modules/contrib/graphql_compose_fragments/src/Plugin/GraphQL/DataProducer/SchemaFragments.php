<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_fragments\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql_compose_fragments\FragmentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Run field item resolution on graphql compose field type plugin.
 *
 * @DataProducer(
 *   id = "schema_fragments",
 *   name = @Translation("Schema Fragments"),
 *   description = @Translation("Returns schema fragments."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Schema fragments result"),
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("string",
 *        label = @Translation("Entity type"),
 *       required = FALSE,
 *     ),
 *     "bundle" = @ContextDefinition("string",
 *       label = @Translation("Bundle type"),
 *       required = FALSE,
 *     ),
 *     "withDependencies" = @ContextDefinition("boolean",
 *       label = @Translation("Include dependencies"),
 *       required = FALSE,
 *     ),
 *   },
 * )
 */
class SchemaFragments extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a SchemaFragments object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\graphql_compose_fragments\FragmentManager $manager
   *   The fragment manager.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected FragmentManager $manager,
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
      $container->get('graphql_compose_fragments.manager'),
    );
  }

  /**
   * Resolve producer fragment items.
   *
   * @param string|null $entity
   *   The entity type.
   * @param string|null $bundle
   *   The bundle type.
   * @param bool|null $withDependencies
   *   Include dependencies.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   The field context.
   *
   * @return mixed
   *   Results from resolution. Array for multiple.
   */
  public function resolve(?string $entity, ?string $bundle, ?bool $withDependencies, FieldContext $context) {

    $types = array_map(
      $this->manager->getFragment(...),
      $this->manager->getTypes()
    );

    if ($entity && $bundle) {
      $filtered = array_filter(
        $types,
        fn ($type) => $type['entity'] === $entity && $type['bundle'] === $bundle
      );
    }
    elseif ($entity) {
      $filtered = array_filter(
        $types,
        fn ($type) => $type['entity'] === $entity
      );
    }
    elseif ($bundle) {
      $filtered = array_filter(
        $types,
        fn ($type) => $type['bundle'] === $bundle
      );
    }
    else {
      return $types;
    }

    if ($withDependencies) {
      // Loop through the types and get the dependencies.
      $getDependencies = function ($type) use (&$getDependencies, &$filtered, $types) {
        foreach ($type['dependencies'] as $dependency) {

          $existing = array_filter(
            $filtered,
            fn ($value) => $value['name'] === $dependency
          );

          if ($existing) {
            continue;
          }

          $matches = array_filter(
            $types,
            fn ($value) => $value['name'] === $dependency
          );

          array_map(function ($value) use (&$filtered, &$getDependencies) {
            $filtered[] = $value;
            $getDependencies($value);
          }, $matches);
        }
      };

      foreach ($filtered as $type) {
        $getDependencies($type);
      }
    }

    return $filtered;
  }

}
