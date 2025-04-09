<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_fragments;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager;
use Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\WrappingType;

/**
 * Controller for the GraphQL Compose fragment controller.
 */
class FragmentManager {

  use StringTranslationTrait;

  /**
   * The entity map.
   *
   * @var array
   */
  protected array $entityMap;

  /**
   * Construct a new Fragment Controller.
   *
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager
   *   The GraphQL Compose schema type manager.
   * @param \Drupal\graphql_compose\Plugin\GraphQLComposeEntityTypeManager $gqlEntityTypeManager
   *   The GraphQL Compose entity type manager.
   */
  public function __construct(
    protected GraphQLComposeSchemaTypeManager $gqlSchemaTypeManager,
    protected GraphQLComposeEntityTypeManager $gqlEntityTypeManager,
  ) {}

  /**
   * Get the prefix.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The prefix.
   */
  public function getPrefix(): MarkupInterface {
    return $this->t('Fragment');
  }

  /**
   * Get the entity map of types to entity/bundle.
   *
   * @return array
   *   The entity map.
   */
  public function getEntityMap(): array {

    if (isset($this->entityMap)) {
      return $this->entityMap;
    }

    foreach ($this->gqlEntityTypeManager->getPluginInstances() as $entity_type) {
      $this->entityMap[$entity_type->getTypeSdl()] = [
        'entity' => $entity_type->getPluginId(),
      ];

      $this->entityMap[$entity_type->getUnionTypeSdl()] = [
        'entity' => $entity_type->getPluginId(),
      ];

      foreach ($entity_type->getBundles() as $bundle) {
        $this->entityMap[$bundle->getTypeSdl()] = [
          'entity' => $entity_type->getPluginId(),
          'bundle' => $bundle->getEntity()->id(),
        ];
      }
    }

    return $this->entityMap;
  }

  /**
   * Return the fragments.
   *
   * @return \GraphQL\Type\Definition\Type[]
   *   The schema types.
   */
  public function getTypes(): array {
    $types = [];

    // Load all entity types.
    foreach ($this->gqlEntityTypeManager->getPluginInstances() as $entity_type) {
      $entity_type->registerTypes();
    }

    // Load all types.
    foreach ($this->gqlSchemaTypeManager->getDefinitions() as $definition) {
      $this->gqlSchemaTypeManager->get($definition['id']);
    }

    // Expand definitions.
    foreach ($this->gqlSchemaTypeManager->getTypes() as $type) {
      if ($type instanceof HasFieldsType) {
        $type->getFields();
      }

      if ($type instanceof ObjectType) {
        $types[$type->name] = $type;
      }
      elseif ($type instanceof UnionType) {
        $type->getTypes();
        $types[$type->name] = $type;
      }
    }

    // Get the object extensions.
    $extensions = $this->getExtensions();

    // Extend the objects fields with extensions fields.
    foreach ($types as $type) {
      if ($type instanceof ObjectType && isset($extensions[$type->name])) {
        $types[$type->name] = new ObjectType([
          'name' => $type->name,
          'fields' => array_merge(
            $type->getFields(),
            $extensions[$type->name]->getFields(),
          ),
        ]);
      }
    }

    ksort($types);

    return $types;
  }

  /**
   * Get the object extensions.
   *
   * @return array
   *   The extensions.
   */
  public function getExtensions(): array {
    $extensions = [];

    foreach ($this->gqlSchemaTypeManager->getExtensions() as $extension) {
      if ($extension instanceof ObjectType) {
        $extensions[$extension->name] = $extension;
      }
    }

    return $extensions;
  }

  /**
   * Get the object fragment.
   *
   * @param \GraphQL\Type\Definition\Type $type
   *   The object type.
   *
   * @return array
   *   The object type and fragment.
   */
  public function getFragment(Type $type): array {
    $map = $this->getEntityMap();
    $content = $this->getFragmentContent($type);

    $fields = implode(PHP_EOL, $content['fields']);
    $fields = PHP_EOL . preg_replace('/^/m', '  ', $fields) . PHP_EOL;

    return [
      'type' => $type,
      'name' => (string) $this->t('@prefix@name', [
        '@prefix' => $this->getPrefix(),
        '@name' => $type->name,
      ]),
      'content' => (string) $this->t('fragment @prefix@name on @name {@fields}', [
        '@prefix' => $this->getPrefix(),
        '@name' => $type->name,
        '@fields' => $fields,
      ]),
      'entity' => $map[$type->name]['entity'] ?? NULL,
      'bundle' => $map[$type->name]['bundle'] ?? NULL,
      'dependencies' => $content['dependencies'],
    ];
  }

  /**
   * Get the object fields.
   *
   * @param \GraphQL\Type\Definition\Type $type
   *   The object type.
   *
   * @return array
   *   The object fields and dependencies.
   */
  public function getFragmentContent(Type $type): array {
    $fields = [];
    $dependencies = [];

    if ($type instanceof UnionType) {
      foreach ($type->getTypes() as $unionType) {
        $fields[] = '  ' . $this->t('... @prefix@name', [
          '@prefix' => $this->getPrefix(),
          '@name' => $unionType->name,
        ]);
        $dependencies[] = (string) $this->t('@prefix@name', [
          '@prefix' => $this->getPrefix(),
          '@name' => $unionType->name,
        ]);
      }
    }
    elseif ($type instanceof ObjectType) {
      foreach ($type->getFields() as $field) {

        // Unwrap the field type.
        $fieldType = $field->getType();
        if ($fieldType instanceof WrappingType) {
          $fieldType = $fieldType->getWrappedType(TRUE);
        }

        if ($fieldType instanceof ObjectType || $fieldType instanceof UnionType) {
          $content = ($fieldType->name === $type->name)
            ? $this->t('# Recursion. Use best judgement or define manually')
            : $this->t('... @prefix@name', [
              '@prefix' => $this->getPrefix(),
              '@name' => $fieldType->name,
            ]);

          $fields[] = $field->name . ' {' . PHP_EOL . '  ' . $content . PHP_EOL . '}';
          $dependencies[] = (string) $this->t('@prefix@name', [
            '@prefix' => $this->getPrefix(),
            '@name' => $fieldType->name,
          ]);
        }
        else {
          $fields[] = $field->name;
        }
      }
    }

    $dependencies = array_unique($dependencies);

    return compact('fields', 'dependencies');
  }

}
