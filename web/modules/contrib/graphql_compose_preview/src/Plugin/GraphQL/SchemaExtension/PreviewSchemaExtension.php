<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\ResolverOnlySchemaExtensionPluginBase;

/**
 * Add entity preview extras to the Schema.
 *
 * @SchemaExtension(
 *   id = "graphql_compose_preview",
 *   name = "GraphQL Compose Preview",
 *   description = @Translation("Add preview extensions to the schema."),
 *   schema = "graphql_compose",
 * )
 */
class PreviewSchemaExtension extends ResolverOnlySchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver(
      'Query',
      'preview',
      $builder->produce('entity_load_preview_token')
        ->map('id', $builder->fromArgument('id'))
        ->map('token', $builder->fromArgument('token'))
        ->map('langcode', $builder->fromArgument('langcode')),
    );
  }

}
