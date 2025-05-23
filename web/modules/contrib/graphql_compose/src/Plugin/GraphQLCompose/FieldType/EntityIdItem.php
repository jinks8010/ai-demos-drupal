<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "entity_id",
 *   type_sdl = "ID",
 * )
 */
class EntityIdItem extends GraphQLComposeFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getProducers(ResolverBuilder $builder): Composite {
    return $builder->compose(
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent()),

      // An id can be null during previews.
      $builder->callback(fn ($id) => $id ?: 0)
    );
  }

}
