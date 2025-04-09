<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_fragments\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\graphql_compose_fragments\FragmentManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Info GraphQL Compose Fragments form.
 */
class FragmentsForm extends ConfigFormBase {

  /**
   * The fragment manager.
   *
   * @var \Drupal\graphql_compose_fragments\FragmentManager
   */
  protected FragmentManager $fragmentManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->fragmentManager = $container->get('graphql_compose_fragments.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graphql_compose_fragments';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['graphql_compose.settings'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfig() {
    return $this->config('graphql_compose.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Convert object type to fragment.
    $fragments = array_map(
      $this->fragmentManager->getFragment(...),
      $this->fragmentManager->getTypes()
    );

    $unions = array_filter(
      $fragments,
      fn ($fragment) => $fragment['type'] instanceof UnionType
    );

    $objects = array_filter(
      $fragments,
      fn ($fragment) => $fragment['type'] instanceof ObjectType
    );

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable fragments on schema'),
      '#default_value' => $this->getConfig()->get('settings.fragments_enabled'),
      '#description' => $this->t('Add %field to the %query query.', [
        '%field' => 'fragments',
        '%query' => 'info',
      ]),
    ];

    $form['warning'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'info' => [
          $this->t('These fragments are intended as a guide, not a solution. Use them to quickly get started building your application.'),
        ],
      ],
    ];

    $form['unions'] = [
      '#type' => 'details',
      '#title' => $this->t('Union Types'),
      'content' => [
        '#theme' => 'graphql_compose_fragments',
        '#attached' => [
          'library' => [
            'graphql_compose_fragments/fragments',
          ],
        ],
        '#fragments' => $unions,
      ],
    ];

    $form['objects'] = [
      '#type' => 'details',
      '#title' => $this->t('Object Types'),
      'content' => [
        '#theme' => 'graphql_compose_fragments',
        '#attached' => [
          'library' => [
            'graphql_compose_fragments/fragments',
          ],
        ],
        '#fragments' => $objects,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getConfig()
      ->set('settings.fragments_enabled', $form_state->getValue('enabled') ?: FALSE)
      ->save();

    _graphql_compose_cache_flush();

    parent::submitForm($form, $form_state);
  }

}
