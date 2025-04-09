<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview\KeyValueStore;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\KeyValueStore\DatabaseStorageExpirable;

/**
 * Utility methods to interact with the tokened private temp store.
 */
class DatabaseStorageExpirableToken extends DatabaseStorageExpirable {

  /**
   * {@inheritdoc}
   *
   * Add preview_token.
   */
  protected function doSetWithExpire($key, $value, $expire) {
    $value->preview_token = Crypt::randomBytesBase64(64);

    return parent::doSetWithExpire($key, $value, $expire);
  }

  /**
   * Escape the token value for searching within a serialized blob.
   *
   * @param string|null $token
   *   The token to escape.
   *
   * @return string
   *   The escaped token.
   */
  protected function escapeToken(?string $token): string {
    $token = $token ?: '';
    $token = preg_replace('/[^a-z0-9_-]/i', '', $token);
    $token = trim($token);

    if (empty($token)) {
      throw new \InvalidArgumentException('Invalid token value.');
    }

    return sprintf('%%;s:13:"preview_token";s:%d:"%s";%%', strlen($token), $token);
  }

  /**
   * Load by token.
   *
   * @param ?string $token
   *   The token to load from the private temp store.
   *
   * @return string|bool
   *   The value.
   */
  public function getKeyByToken(?string $token) {
    try {
      $data = $this->connection->query(
        'SELECT [name], [value] FROM {' . $this->connection->escapeTable($this->table) . '} WHERE value LIKE :token AND collection = :collection',
        [
          ':token' => $this->escapeToken($token),
          ':collection' => $this->collection,
        ])->fetchObject();

      if (!$data || ($this->serializer->decode($data->value)->preview_token ?? FALSE) !== $token) {
        return FALSE;
      }

      return $data->name;
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
    return FALSE;
  }

  /**
   * Load by token.
   *
   * @param string $name
   *   The name to load from the private temp store.
   *
   * @return string|bool
   *   The value.
   */
  public function getTokenByKey($name) {
    try {
      $data = $this->connection->query(
        'SELECT [value] FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name = :name AND collection = :collection',
        [
          ':name' => $name,
          ':collection' => $this->collection,
        ])->fetchObject();

      if (!$data) {
        return FALSE;
      }

      return $this->serializer->decode($data->value)->preview_token ?? FALSE;
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
    return FALSE;
  }

}
