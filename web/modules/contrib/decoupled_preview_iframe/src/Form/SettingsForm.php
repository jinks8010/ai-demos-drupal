<?php

namespace Drupal\decoupled_preview_iframe\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Decoupled Preview Iframe Settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * Construct a new Decoupled preview settings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Defines the configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Manage drupal modules.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    EntityTypeRepositoryInterface $entity_type_repository,
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->entityTypeRepository = $entity_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('entity_type.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'decoupled_preview_iframe_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['decoupled_preview_iframe.settings'];
  }

  /**
   * Returns an array of supported entity types to enable preview.
   *
   * @return string[]
   *   Array of common entity types with canonical urls.
   */
  protected function supportedEntityTypes() {
    return [
      'node',
      'media',
      'taxonomy_term',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('decoupled_preview_iframe.settings');
    $types = $this->getPreviewTypeOptions();
    $default_types = $config->get('preview_types');

    $form['redirect'] = [
      '#type' => 'fieldset',
      '#name' => 'redirect',
    ];

    $form['redirect']['redirect_anonymous'] = [
      '#type' => 'checkbox',
      '#default_value' => boolval($config->get('redirect_anonymous')),
      '#title' => $this->t('Enable Anonymous redirects'),
      '#group' => 'redirect',
    ];

    $form['redirect']['redirect_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Redirect URL'),
      '#description' => $this->t('Enter the URL for the frontend website: Example: <em>http://site.com</em>'),
      '#default_value' => $config->get('redirect_url'),
      '#states' => [
        'visible' => [
          ':input[name="redirect_anonymous"]' => ['checked' => TRUE],
        ],
      ],
      '#group' => 'redirect',
    ];

    $form['preview'] = [
      '#type' => 'fieldset',
      '#name' => 'preview',
    ];

    $form['preview']['preview_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Preview URL'),
      '#description' => $this->t('Enter the preview URL for the frontend website: Example: <em>http://localhost:8080</em>'),
      '#default_value' => $config->get('preview_url'),
    ];

    $form['preview']['preview_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Preview enabled types'),
      '#description' => $this->t('Enable the decoupled preview iframe for the selected entity types.'),
      '#description_display' => 'before',
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    foreach ($types as $type_id => $type) {
      $form['preview']['preview_types'][$type_id] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('@label', ['@label' => $type['label']]),
        '#options' => $type['bundles'],
        '#default_value' => $default_types[$type_id] ?? [],
      ];
    }

    $form['preview']['route_sync'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Route Syncing'),
      '#default_value' => !empty($config->get('route_sync')) ? $config->get('route_sync') : 'DECOUPLED_PREVIEW_IFRAME_ROUTE_SYNC',
      '#description' => $this->t('Sync route changes inside the iframe preview with your Drupal site.<br /><br /><em>DECOUPLED_PREVIEW_IFRAME_ROUTE_SYNC (default) or NEXT_DRUPAL_ROUTE_SYNC (if using Next.js module)</em>'),
    ];

    $form['preview']['draft_provider'] = [
      '#type' => 'select',
      '#title' => 'Preview Provider',
      '#options' => $this->getDraftProviders(),
      '#default_value' => $config->get('draft_provider'),
      '#description' => $this->t('Select a provider to provide access to Node Draft data.<br /><br /><em>For GraphQL Compose: Install graphql_compose_preview module to support Draft Preview.</em>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns the draft providers.
   *
   * @return array
   *   An array of draft providers.
   */
  public function getDraftProviders() {
    $draft_providers = [
      'none' => $this->t('None'),
    ];
    $draft_providers_modules = [
      'graphql_compose_preview',
    ];
    foreach ($draft_providers_modules as $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        $draft_providers[$module] = $this->moduleHandler->getName($module);
      }
    }

    return $draft_providers;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('decoupled_preview_iframe.settings');
    $preview_types = [];
    foreach ($form_state->getValue('preview_types') as $entity_type => $bundles) {
      $filtered_bundles = array_filter($bundles);
      if (!empty($filtered_bundles)) {
        $preview_types[$entity_type] = $filtered_bundles;
      }
    }

    $config
      ->set('redirect_anonymous', boolval($form_state->getValue('redirect_anonymous')))
      ->set('redirect_url', $form_state->getValue('redirect_url'))
      ->set('preview_url', $form_state->getValue('preview_url'))
      ->set('preview_types', $preview_types)
      ->set('route_sync', $form_state->getValue('route_sync'))
      ->set('draft_provider', $form_state->getValue('draft_provider'))
      ->save();
  }

  /**
   * Helper function to get supported entity types and bundles.
   *
   * @return array
   *   An array of entity types and their bundles keyed by type and bundle.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getPreviewTypeOptions(): array {
    $types = [];
    foreach ($this->supportedEntityTypes() as $entity_type) {
      if ($entity_type === 'media') {
        continue;
      }
      $definition = $this->entityTypeManager->getDefinition($entity_type);
      if ($bundle_entity_type = $definition->getBundleEntityType()) {
        $bundles = $this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple();
        if (!empty($bundles)) {
          $types[$entity_type] = [
            'label' => $definition->getLabel(),
            'bundles' => [],
          ];
          foreach ($bundles as $bundle_id => $bundle) {
            $types[$entity_type]['bundles'][$bundle_id] = $bundle->label();
          }
        }
      }
    }

    return $types;
  }

}
