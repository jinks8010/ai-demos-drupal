<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of preview token.
 *
 * @FieldType(
 *   id = "preview_token",
 *   label = @Translation("Preview Token"),
 *   category = "preview_token",
 *   default_formatter = "preview_token_link",
 *   no_ui = TRUE,
 * )
 */
class PreviewTokenItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Preview token value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $entity = $this->getEntity();
    $value = $this->get('value')->getValue();

    return empty($entity->in_preview) || $value === NULL || $value === '';
  }

}
