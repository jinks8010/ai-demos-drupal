<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Access\NodePreviewAccessCheck;
use Drupal\node\NodeInterface;

/**
 * Change the access to 'view' if we're using a token to view a preview.
 */
class NodePreviewAccessCheckToken extends NodePreviewAccessCheck {

  /**
   * {@inheritdoc}
   *
   * @see graphql_compose_preview_node_access()
   */
  public function access(AccountInterface $account, NodeInterface $node_preview) {
    $tokenHelper = \Drupal::service('graphql_compose_preview.token_helper');

    return $tokenHelper->access($node_preview)
      ? $node_preview->access('view', $account, TRUE)
      : parent::access($account, $node_preview);
  }

}
