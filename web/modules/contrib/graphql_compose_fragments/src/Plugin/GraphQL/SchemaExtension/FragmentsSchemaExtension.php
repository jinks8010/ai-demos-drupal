<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_fragments\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\ResolverOnlySchemaExtensionPluginBase;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @SchemaExtension(
 *   id = "graphql_compose_fragments",
 *   name = "GraphQL Compose Fragments",
 *   description = @Translation("Add fragments support to schema."),
 *   schema = "graphql_compose",
 * )
 *
 * @internal
 */
class FragmentsSchemaExtension extends ResolverOnlySchemaExtensionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $config = $this->configFactory->get('graphql_compose.settings');
    if (!$config->get('settings.fragments_enabled')) {
      return;
    }

    $registry->addFieldResolver(
      'SchemaInformation',
      'fragments',
      $builder->produce('schema_fragments')
        ->map('entity', $builder->fromArgument('entity'))
        ->map('bundle', $builder->fromArgument('bundle'))
        ->map('withDependencies', $builder->fromArgument('withDependencies'))
    );

    $registry->addFieldResolver(
      'SchemaFragment',
      'name',
      $builder->callback(fn (array $fragment) => $fragment['name'])
    );

    $registry->addFieldResolver(
      'SchemaFragment',
      'type',
      $builder->compose(
        $builder->callback(fn (array $fragment) => $fragment['type']),
        $builder->callback(fn (Type $type) => $type->name),
      ),
    );

    $registry->addFieldResolver(
      'SchemaFragment',
      'class',
      $builder->compose(
        $builder->callback(fn (array $fragment) => $fragment['type']),
        $builder->callback(fn (Type $type) => $type::class),
      ),
    );

    $registry->addFieldResolver(
      'SchemaFragment',
      'content',
      $builder->callback(fn (array $fragment) => $fragment['content']),
    );

    $registry->addFieldResolver(
      'SchemaFragment',
      'entity',
      $builder->callback(fn (array $fragment) => $fragment['entity']),
    );

    $registry->addFieldResolver(
      'SchemaFragment',
      'bundle',
      $builder->callback(fn (array $fragment) => $fragment['bundle']),
    );

    $registry->addFieldResolver(
      'SchemaFragment',
      'dependencies',
      $builder->callback(fn (array $fragment) => $fragment['dependencies']),
    );
  }

}
