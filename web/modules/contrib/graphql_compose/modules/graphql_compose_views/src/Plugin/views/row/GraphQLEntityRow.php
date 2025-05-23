<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\views\row;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin which displays entities as raw data.
 *
 * @ViewsRow(
 *   id = "graphql_entity",
 *   title = @Translation("Entity"),
 *   help = @Translation("Use entities as row data."),
 *   display_types = {"graphql"},
 * )
 */
class GraphQLEntityRow extends RowPluginBase {

  use EntityTranslationRenderTrait {
    getEntityTranslationRenderer as getEntityTranslationRendererBase;
  }

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = FALSE;

  /**
   * Constructs a GraphQLEntityRow object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   Drupal entity type bundle service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Drupal language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   Drupal entity repository.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeBundleInfo $entityTypeBundleInfo,
    protected LanguageManagerInterface $languageManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityRepositoryInterface $entityRepository,
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
      $container->get('entity_type.bundle.info'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    if ($entity = $this->getEntityFromRow($row)) {
      return $this->view->getBaseEntityType()
        ? $this->getEntityTranslationByRelationship($entity, $row)
        : $entity;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTranslationRenderer() {
    return $this->view->getBaseEntityType()
      ? $this->getEntityTranslationRendererBase()
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityRepository() {
    return $this->entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    if ($entityType = $this->view->getBaseEntityType()) {
      return $entityType->id();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeBundleInfo() {
    return $this->entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * Retrieves the entity object from a result row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The views result row object.
   *
   * @return null|\Drupal\Core\Entity\EntityInterface
   *   The extracted entity object or NULL if it could not be retrieved.
   */
  protected function getEntityFromRow(ResultRow $row) {

    if (isset($row->_entity) && $row->_entity instanceof EntityInterface) {
      return $row->_entity;
    }

    if (isset($row->_object) && $row->_object instanceof EntityAdapter) {
      return $row->_object->getValue();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();

    if ($this->view->getBaseEntityType()) {
      $this->getEntityTranslationRenderer()->query($this->view->getQuery());
    }
  }

}
