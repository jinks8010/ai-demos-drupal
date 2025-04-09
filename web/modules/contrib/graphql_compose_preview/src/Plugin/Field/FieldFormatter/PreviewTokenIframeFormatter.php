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
 * Plugin implementation of the 'preview_token_iframe' formatter.
 *
 * @FieldFormatter(
 *   id = "preview_token_iframe",
 *   label = @Translation("Token preview iframe"),
 *   field_types = {
 *     "preview_token",
 *   },
 * )
 */
class PreviewTokenIframeFormatter extends FormatterBase {

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
    return getenv('GRAPHQL_COMPOSE_PREVIEW_URL') ?: $this->getSetting('iframe_url');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'iframe_url' => 'https://my.frontend/preview/[node:preview:uuid]/[node:preview:token]',
      'class' => '',
      'width' => '100%',
      'height' => '500',
      'allow' => 'fullscreen autoplay',
      'transparency' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays as iframe.');
    $summary[] = $this->t('URL: @url', ['@url' => $this->getLinkUrl()]);

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
      'iframe_url' => [
        '#type' => 'textfield',
        '#title' => $this->t('Base URL'),
        '#default_value' => $this->getLinkUrl(),
        '#required' => TRUE,
        '#disabled' => getenv('GRAPHQL_COMPOSE_PREVIEW_URL'),
        '#description' => $this->t('
          The URL for your iframe. Use the tokens %uuid, %token or %url or environment variable %env.',
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
        '#description' => $this->t('The CSS class to apply to the iframe.'),
      ],
      'width' => [
        '#type' => 'textfield',
        '#title' => $this->t('Width'),
        '#default_value' => $this->getSetting('width'),
        '#description' => $this->t('The width of the iframe.'),
      ],
      'height' => [
        '#type' => 'textfield',
        '#title' => $this->t('Height'),
        '#default_value' => $this->getSetting('height'),
        '#description' => $this->t('The height of the iframe.'),
      ],
      'allow' => [
        '#type' => 'textfield',
        '#title' => $this->t('Allow'),
        '#default_value' => $this->getSetting('allow'),
        '#description' => $this->t('The allow attribute of the iframe.'),
      ],
      'transparency' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow transparency'),
        '#default_value' => $this->getSetting('transparency'),
        '#description' => $this->t('Allow transparency in the iframe.'),
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

    $access = $this->tokenHelper->access($entity);
    $cache_metadata = new BubbleableMetadata();

    $src = $this->token->replace(
      $this->getLinkUrl(),
      ['node' => $entity],
      ['clear' => FALSE]
    );

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'token_preview_iframe',
        '#node' => $entity,
        '#preview_token' => $item,
        '#preview_token_url' => $url,
        '#preview_token_access' => $access,
        '#attributes' => new Attribute([
          'src' => $src,
          'class' => $this->getSetting('class'),
          'width' => $this->getSetting('width'),
          'height' => $this->getSetting('height'),
          'allow' => $this->getSetting('allow'),
          'allowtransparency' => $this->getSetting('transparency'),
          'frameborder' => 0,
        ]),
      ];
    }

    $cache_metadata->addCacheableDependency($url);
    $cache_metadata->applyTo($element);

    return $element;
  }

}
