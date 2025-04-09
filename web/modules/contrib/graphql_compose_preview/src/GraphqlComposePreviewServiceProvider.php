<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\graphql_compose_preview\Access\NodePreviewAccessCheckToken;
use Drupal\graphql_compose_preview\KeyValueStore\KeyValueDatabaseExpirableTokenFactory;
use Drupal\graphql_compose_preview\TempStore\PrivateTempStoreTokenFactory;

/**
 * Modify the container bindings.
 */
class GraphqlComposePreviewServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('keyvalue.expirable.database')) {
      $definition = $container->getDefinition('keyvalue.expirable.database');
      $definition->setClass(KeyValueDatabaseExpirableTokenFactory::class);
    }

    if ($container->hasDefinition('tempstore.private')) {
      $definition = $container->getDefinition('tempstore.private');
      $definition->setClass(PrivateTempStoreTokenFactory::class);
    }

    if ($container->hasDefinition('access_check.node.preview')) {
      $definition = $container->getDefinition('access_check.node.preview');
      $definition->setClass(NodePreviewAccessCheckToken::class);
    }
  }

}
