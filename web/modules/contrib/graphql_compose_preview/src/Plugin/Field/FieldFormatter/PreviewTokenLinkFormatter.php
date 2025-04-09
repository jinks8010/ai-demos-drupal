<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_preview\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Utility\Token;
use Drupal\graphql_compose_preview\TokenHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'preview_token_link' formatter.
 *
 * @FieldFormatter(
 *   id = "preview_token_link",
 *   label = @Translation("Token preview link"),
 *   field_types = {
 *     "preview_token",
 *   },
 * )
 */
class PreviewTokenLinkFormatter extends FormatterBase {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * The token helper service.
   *
   * @var \Drupal\graphql_compose_preview\TokenHelper
   */
  protected TokenHelper $tokenHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->token = $container->get('token');
    $instance->tokenHelper = $container->get('graphql_compose_preview.token_helper');

    return $instance;
  }

  /**
   * Get the iframe URL.
   *
   * @return string
   *   The iframe URL.
   */
  protected function getLinkUrl() {
    return getenv('GRAPHQL_COMPOSE_PREVIEW_URL') ?: $this->getSetting('link_url');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title' => t('Preview with token'),
      'link_url' => '',
      'class' => 'button button--small',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays as link.');
    $summary[] = $this->t('Link title: @title', ['@title' => $this->getSetting('title')]);

    if ($this->getLinkUrl()) {
      $summary[] = $this->t('Link URL: @url', ['@url' => $this->getLinkUrl()]);
    }

    if ($this->getSetting('class')) {
      $summary[] = $this->t('CSS class: @class', ['@class' => $this->getSetting('class')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Link title'),
        '#default_value' => $this->getSetting('title'),
        '#description' => $this->t('The title to display in the link.'),
      ],
      'link_url' => [
        '#type' => 'textfield',
        '#title' => $this->t('Base URL'),
        '#default_value' => $this->getLinkUrl(),
        '#disabled' => getenv('GRAPHQL_COMPOSE_PREVIEW_URL'),
        '#description' => $this->t('
          The URL for your iframe. Leave blank for default. Use the tokens %uuid, %token or %url or environment variable %env.',
          [
            '%uuid' => '[node:preview:uuid]',
            '%token' => '[node:preview:token]',
            '%url' => '[node:preview:url]',
            '%env' => 'GRAPHQL_COMPOSE_PREVIEW_URL',
          ],
        ),
        '#token_types' => ['node'],
      ],
      'token_help' => [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node'],
      ],
      'class' => [
        '#type' => 'textfield',
        '#title' => $this->t('CSS class'),
        '#default_value' => $this->getSetting('class'),
        '#description' => $this->t('The CSS class to apply to the link.'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $entity = $items->getEntity();
    if (!$entity) {
      return [];
    }

    $url = $this->tokenHelper->url($entity);
    if (!$url) {
      return [];
    }

    if ($this->getLinkUrl()) {
      $href = $this->token->replace(
        $this->getLinkUrl(),
        ['node' => $entity],
        ['clear' => FALSE]
      );
    }
    else {
      $href = $url->getGeneratedUrl();
    }

    $access = $this->tokenHelper->access($entity);
    $cache_metadata = new BubbleableMetadata();

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'token_preview_link',
        '#node' => $entity,
        '#preview_token' => $item,
        '#preview_token_url' => $url,
        '#preview_token_access' => $access,
        '#title' => $this->getSetting('title'),
        '#attributes' => new Attribute([
          'class' => $this->getSetting('class'),
          'href' => $href,
        ]),
      ];
    }

    $cache_metadata->addCacheableDependency($url);
    $cache_metadata->applyTo($element);

    return $element;
  }

}
