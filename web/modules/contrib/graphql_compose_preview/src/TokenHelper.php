<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview;

use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Preview token utility class.
 */
class TokenHelper {

  /**
   * Construct the preview helper utility class.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The url generator.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected UrlGeneratorInterface $urlGenerator,
  ) {}

  /**
   * Get the preview token from the node.
   *
   * @param \Drupal\node\NodeInterface $node_preview
   *   The node to get the preview token from.
   *
   * @return string|null
   *   The preview token or null if not found.
   */
  public function token(NodeInterface $node_preview): ?string {
    return $node_preview->get('preview_token')->value ?: NULL;
  }

  /**
   * Check if the preview access is via token.
   *
   * @param \Drupal\node\NodeInterface $node_preview
   *   The node to get the preview token from.
   *
   * @return bool
   *   Access to the preview is via access token.
   */
  public function access(NodeInterface $node_preview): bool {
    return $node_preview->get('preview_token_access')->value ?: FALSE;
  }

  /**
   * Get the token url to a preview.
   *
   * @param \Drupal\node\NodeInterface $node_preview
   *   The node to get the preview token from.
   * @param array $parameters
   *   Additional parameters to pass to the url generator.
   * @param array $options
   *   Additional options to pass to the url generator.
   *
   * @return \Drupal\Core\GeneratedUrl|null
   *   URL to the preview with token.
   */
  public function url(NodeInterface $node_preview, array $parameters = [], array $options = []): ?GeneratedUrl {
    $token = $this->token($node_preview);

    $parameters += [
      'node_preview' => $node_preview->uuid(),
      'view_mode_id' => 'full',
    ];

    $options['query']['token'] = $token;

    return $token
      ? $this->urlGenerator->generateFromRoute('entity.node.preview', $parameters, $options, TRUE)
      : NULL;
  }

  /**
   * Get the preview token from the request.
   *
   * @return string|null
   *   The preview token or null if not found.
   */
  public function getRequestToken(): ?string {
    $current = $this->requestStack->getCurrentRequest();
    return $current->query->get('token') ?: $current->attributes->get('_graphql_compose_preview_token');
  }

  /**
   * Get the non-user specific store key.
   *
   * @param string|null $key
   *   The key to get the loose key from.
   *
   * @return string|null
   *   The loose key or null if not found
   */
  public function getLooseKey(?string $key): ?string {
    return explode(':', $key ?: '')[1] ?? NULL;
  }

  /**
   * Get preview entity from store data.
   *
   * @param \Drupal\Core\Form\FormStateInterface|null $data
   *   The data to get the preview entity from.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The preview entity.
   */
  public function getPreviewEntity(?FormStateInterface $data): ?ContentEntityInterface {
    if (!$data instanceof FormStateInterface) {
      return NULL;
    }

    $node_form = $data->getFormObject();
    if (!$node_form instanceof ContentEntityFormInterface) {
      return NULL;
    }

    $entity = $node_form->getEntity();

    return $entity instanceof ContentEntityInterface ? $entity : NULL;
  }

}
