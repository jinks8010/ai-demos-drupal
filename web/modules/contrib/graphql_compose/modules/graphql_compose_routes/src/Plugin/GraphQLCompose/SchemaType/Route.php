<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_routes\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "Route",
 * )
 */
class Route extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new InterfaceType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('Routes represent incoming requests that resolve to content.'),
      'fields' => fn() => [
        'url' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('URL of this route.'),
        ],
        'internal' => [
          'type' => Type::nonNull(Type::boolean()),
          'description' => (string) $this->t('Whether this route is internal or external.'),
        ],
      ],
    ]);

    return $types;
  }

  /**
   * Disable automatic entity types.
   */
  public function getExtensions(): array {

    $extensions = parent::getExtensions();

    $extensions[] = new ObjectType([
      'name' => 'Query',
      'fields' => fn() => [
        'route' => [
          'type' => static::type('RouteUnion'),
          'description' => (string) $this->t('Load a Route by path.'),
          'args' => array_filter([
            'path' => [
              'type' => Type::nonNull(Type::string()),
              'description' => (string) $this->t('Internal path to load. Eg /about'),
            ],
            'revision' => [
              'type' => Type::id(),
              'description' => (string) $this->t('Optionally set the revision of the entity. Eg current, latest, or an ID.'),
            ],
            'langcode' => $this->languageManager->isMultilingual() ? [
              'type' => Type::string(),
              'description' => (string) $this->t('Optionally set the response language. Eg en, ja, fr. Setting this langcode will change the current language of the entire response.'),
            ] : [],
          ]),
        ],
      ],
    ]);

    return $extensions;
  }

}
