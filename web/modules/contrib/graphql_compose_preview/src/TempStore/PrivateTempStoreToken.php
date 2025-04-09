<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview\TempStore;

use Drupal\Core\TempStore\PrivateTempStore;

/**
 * A private temp store token interception.
 */
class PrivateTempStoreToken extends PrivateTempStore {

  /**
   * The key/value storage object used for this data.
   *
   * @var \Drupal\graphql_compose_preview\KeyValueStore\DatabaseStorageExpirableToken
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function get($key) {

    /** @var \Drupal\graphql_compose_preview\TokenHelper $tokenHelper */
    $tokenHelper = \Drupal::service('graphql_compose_preview.token_helper');
    $token = $tokenHelper->getRequestToken();

    // Get the key and token from the store.
    $store_key = $token ? $this->storage->getKeyByToken($token) : $this->createkey($key);
    $store_token = $this->storage->getTokenByKey($store_key);
    $loose_key = $tokenHelper->getLooseKey($store_key ?: '');

    $token_access = $token && ($key === $loose_key);

    if ($token) {
      $data = $token_access ? $this->storage->get($store_key)?->data : NULL;
    }
    else {
      $data = parent::get($key);
    }

    // Add token value to node computed property.
    if ($entity = $tokenHelper->getPreviewEntity($data)) {
      $entity->set('preview_token', $store_token);
      $entity->set('preview_token_access', $token_access);
    }

    return $data;
  }

}
