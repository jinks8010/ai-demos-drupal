<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview\KeyValueStore;

use Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory;

/**
 * A key value database storage with token interception.
 */
class KeyValueDatabaseExpirableTokenFactory extends KeyValueDatabaseExpirableFactory {

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if ($collection === 'tempstore.private.node_preview') {
      $this->storages[$collection] ??= new DatabaseStorageExpirableToken($collection, $this->serializer, $this->connection);
    }

    return parent::get($collection);
  }

}
