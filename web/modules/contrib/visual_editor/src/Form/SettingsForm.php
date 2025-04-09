<?php

declare(strict_types=1);

namespace Drupal\visual_editor\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Visual Editor SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Construct a new Settings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Defines the configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The Drupal entity type bundle service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Drupal entity field manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Manage drupal modules.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visual_editor_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['visual_editor.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['basic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic settings:'),
      '#name' => 'basic',
    ];

    $form['basic']['disable_styles'] = [
      '#type' => 'checkbox',
      '#title' => 'Disable default Off-Canvas Styles',
      '#default_value' => boolval($this->config('visual_editor.settings')->get('disable_styles')),
      '#group' => 'basic',
    ];

    $form['basic']['open_load'] = [
      '#type' => 'checkbox',
      '#title' => 'Open Off-Canvas on page load',
      '#default_value' => boolval($this->config('visual_editor.settings')->get('open_load')),
      '#group' => 'basic',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('visual_editor.settings')
      // ->set('framework', $form_state->getValue('framework'))
      ->set('disable_styles', boolval($form_state->getValue('disable_styles')))
      ->set('open_load', boolval($form_state->getValue('open_load')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
