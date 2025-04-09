<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview\TempStore;

use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * A private temp store token interception.
 */
class PrivateTempStoreTokenFactory extends PrivateTempStoreFactory {

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if ($collection === 'node_preview') {
      $storage = $this->storageFactory->get("tempstore.private.$collection");
      return new PrivateTempStoreToken($storage, $this->lockBackend, $this->currentUser, $this->requestStack, $this->expire);
    }

    return parent::get($collection);
  }

}
