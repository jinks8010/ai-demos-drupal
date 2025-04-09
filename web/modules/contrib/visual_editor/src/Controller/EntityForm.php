<?php

namespace Drupal\visual_editor\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Controller to edit Paragraphs.
 */
class EntityForm extends ControllerBase {

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityRepository = $container->get('entity.repository');

    return $instance;
  }

  /**
   * Checks access to the node being viewed.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param string $node_uuid
   *   The node uuid to check for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function access(AccountInterface $account, string $node_uuid) {
    $node = $this->entityRepository->loadEntityByUuid('node', $node_uuid);

    if ($node instanceof NodeInterface) {
      return $node->access('update', $account, TRUE);
    }

    return AccessResult::forbidden();
  }

  /**
   * Load the form for a node to edit.
   */
  public function nodeEdit($node_uuid) {
    $storage = "node";
    $display = "visual_editor";
    $node = $this->calculateEntity($storage, $node_uuid);
    $formObject = $this->entityTypeManager()->getFormObject($storage, $display);
    $formObject->setEntity($node);
    $form = $this->formBuilder()->getForm($formObject);
    $menu = [
      [
        'label' => $node->type->entity->label(),
      ],
    ];

    return [
      '#theme' => 'visual_editor__dialog',
      '#form' => $form,
      '#menu' => $menu,
    ];
  }

  /**
   * Function to calculate the entity based on storage and entity value.
   */
  private function calculateEntity($storage, $uuid) {
    $entity = $this->entityRepository->loadEntityByUuid($storage, $uuid);

    if (!$entity) {
      throw new \Exception('Entity not found.');
    }

    return $entity;
  }

}
