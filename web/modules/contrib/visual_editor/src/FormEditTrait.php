<?php

declare(strict_types=1);

namespace Drupal\visual_editor;

/**
 * Trait for \Drupal\visual_editor\Form\NodeEditForm.
 */
trait FormEditTrait {

  /**
   * Stores the isLatestRevision status.
   *
   * @var bool
   */
  protected $isLatestRevision = FALSE;

  /**
   * Get the isLatestRevision status.
   *
   * @return bool
   *   The isLatestRevision status.
   */
  public function isLatestRevision() {
    return $this->isLatestRevision;
  }

  /**
   * Sets the entity for sidebar edit form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return void
   *   Return nothing.
   */
  public function calculateNodeEntity(&$form, &$form_state) {
    // Check if the entity is not the latest published revision.
    $entity = $form_state->getFormObject()->getEntity();
    // Set the isLatestRevision status.
    $this->isLatestRevision = $entity->isLatestRevision();

    // If not the latest revision, load the latest revision.
    if (!$entity->isLatestRevision()) {
      $revisionId = $this->entityTypeManager
        ->getStorage('node')
        ->getLatestRevisionId($entity->id());

      $node = $this->entityTypeManager
        ->getStorage('node')
        ->loadRevision($revisionId);

      $form_state->getFormObject()->setEntity($node);
      $form_state->setRebuild();

      $this->entity = $node;
      return;
    }

    // Check if the form is in preview mode.
    $query = \Drupal::request()->query;
    $isPreview = $query->has('preview') && $query->get('preview') == 'true';

    // Try to restore from temp store, this must be done before calling
    // parent::form().
    $store = $this->tempStoreFactory->get('node_preview');
    $uuid = $form_state->getFormObject()->getEntity()->uuid();
    $preview = $store->get($uuid);

    if ($isPreview && !$form_state->isRebuilding() && $uuid && $preview) {
      /** @var \Drupal\Core\Form\FormStateInterface $preview */
      $form_state->setStorage($preview->getStorage());
      $form_state->setUserInput($preview->getUserInput());

      // Rebuild the form.
      $form_state->setRebuild();

      // The combination of having user input and rebuilding the form means
      // that it will attempt to cache the form state which will fail if it is
      // a GET request.
      $form_state->setRequestMethod('POST');

      $this->entity = $preview->getFormObject()->getEntity();
      $this->entity->in_preview = NULL;

      $form_state->set('has_been_previewed', TRUE);
      return;
    }

    $this->entity = $form_state->getFormObject()->getEntity();
  }

}
