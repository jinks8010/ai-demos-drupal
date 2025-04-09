<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_fragments\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "SchemaFragment",
 * )
 */
class SchemaFragment extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    $config = $this->configFactory->get('graphql_compose.settings');
    if (!$config->get('settings.fragments_enabled')) {
      return $types;
    }

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A fragment representing a type within the schema.'),
      'fields' => fn() => [
        'type' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The scheme type of the fragment.'),
        ],
        'name' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The name of the fragment.'),
        ],
        'class' => [
          'type' => Type::nonNull(Type::string()),
          'description' => (string) $this->t('The base graphql type.'),
        ],
        'content' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The fragment content.'),
        ],
        'entity' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The entity type of the fragment.'),
        ],
        'bundle' => [
          'type' => Type::string(),
          'description' => (string) $this->t('The bundle type of the fragment.'),
        ],
        'dependencies' => [
          'type' => Type::listOf(Type::string()),
          'description' => (string) $this->t('The list of fragments this fragment depends on.'),
        ],
      ],
    ]);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions(): array {
    $extensions = parent::getExtensions();

    $config = $this->configFactory->get('graphql_compose.settings');
    if (!$config->get('settings.fragments_enabled')) {
      return $extensions;
    }

    $extensions[] = new ObjectType([
      'name' => 'SchemaInformation',
      'fields' => function () {
        return [
          'fragments' => [
            'type' => Type::nonNull(Type::listOf(Type::nonNull(static::type('SchemaFragment')))),
            'description' => (string) $this->t('List of fragments available.'),
            'args' => [
              'entity' => [
                'type' => Type::string(),
                'description' => (string) $this->t('The entity type to filter fragments for.'),
              ],
              'bundle' => [
                'type' => Type::string(),
                'description' => (string) $this->t('The bundle type to filter fragments for.'),
              ],
              'withDependencies' => [
                'type' => Type::boolean(),
                'description' => (string) $this->t('Include dependencies in the result.'),
              ],
            ],
          ],
        ];
      },
    ]);

    return $extensions;
  }

}
