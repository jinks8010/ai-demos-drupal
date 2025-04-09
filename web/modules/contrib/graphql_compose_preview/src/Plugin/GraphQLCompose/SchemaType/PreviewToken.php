<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "PreviewToken",
 * )
 */
class PreviewToken extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions(): array {
    $extensions = [];

    $extensions[] = new ObjectType([
      'name' => 'Query',
      'fields' => fn() => [
        'preview' => [
          'type' => static::type('NodeUnion'),
          'description' => (string) $this->t('Load a content preview.'),
          'args' => [
            'id' => [
              'type' => Type::nonNull(Type::id()),
              'description' => (string) $this->t('The content UUID.'),
            ],
            'token' => [
              'type' => Type::string(),
              'description' => (string) $this->t('A preview access token.'),
            ],
            'langcode' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Optionally set the response language. Eg en, ja, fr. Setting this langcode will change the current language of the entire response.'),
            ],
          ],
        ],
      ],
    ]);

    return $extensions;
  }

}
