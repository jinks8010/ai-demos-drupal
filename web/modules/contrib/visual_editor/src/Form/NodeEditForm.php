<?php

declare(strict_types=1);

namespace Drupal\visual_editor\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeForm;
use Drupal\visual_editor\FormEditTrait;

/**
 * The NodeEditForm class.
 */
class NodeEditForm extends NodeForm {
  use FormEditTrait;
  use AjaxFormHelperTrait;

  /**
   * {@inheritdoc}
   *
   * Overridden to store the root parent entity.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Calculate if preview | latest published revision.
    $this->calculateNodeEntity($form, $form_state);
    // Build the form.
    $form = parent::buildForm($form, $form_state);

    // Add status aka published field to meta fieldset.
    $form['meta']['status'] = $form['status'];
    $form['meta']['status']['#group'] = NULL;
    $form['meta']['status']['#weight'] = 0;

    // Hide Preview button.
    $form['actions']['preview']['#access'] = $this->isLatestRevision();
    // Hide Delete button.
    $form['actions']['delete']['#access'] = FALSE;
    // Add ajax callback to submit button.
    $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
    // @todo static::ajaxSubmit() requires data-drupal-selector to be the same
    //   between the various Ajax requests. A bug in
    //   \Drupal\Core\Form\FormBuilder prevents that from happening unless
    //   $form['#id'] is also the same. Normally, #id is set to a unique HTML
    //   ID via Html::getUniqueId(), but here we bypass that in order to work
    //   around the data-drupal-selector bug. This is okay so long as we
    //   assume that this form only ever occurs once on a page. Remove this
    //   workaround in https://www.drupal.org/node/2897377.
    $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => 'Close',
      '#weight' => 100,
      '#ajax' => [
        'callback' => 'visual_editor_close_dialog',
        'disable-refocus' => TRUE,
        'progress' => 'none',
      ],
      '#attributes' => [
        'class' => [
          'use-ajax',
          'dialog-cancel',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    // Invoke Form lifecycle events.
    $this->submitForm($form, $form_state);
    $this->save($form, $form_state);
    $this->buildForm($form, $form_state);
    // Invoke Ajax Commands to reload iframe and dialog.
    $response = new AjaxResponse();
    $arguments = [
      'node', $this->entity->id(),
    ];
    $response->addCommand(new InvokeCommand(NULL, 'visualEditorReload', $arguments));

    return $response;
  }

}
