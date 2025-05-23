<?php

declare(strict_types=1);

namespace Drupal\graphql_compose\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Replace global tokens with token plain text.
 *
 * @DataProducer(
 *   id = "plain_token",
 *   name = @Translation("Plain token producer"),
 *   description = @Translation("Token replacement on a value."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Token replaced value"),
 *   ),
 *   consumes = {
 *     "value" = @ContextDefinition("any",
 *       label = @Translation("Value for token replacement"),
 *     ),
 *   },
 * )
 */
class PlainTokenProducer extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token|null
   */
  protected ?Token $token;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->token = $container->get('token', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    $instance->renderer = $container->get('renderer');

    return $instance;
  }

  /**
   * Resolve producer field items.
   *
   * @param mixed $value
   *   Consumption options passed to the field.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   The cache context.
   *
   * @return mixed
   *   Results from resolution. Array for multiple.
   */
  public function resolve($value, FieldContext $context) {

    if (!$this->token) {
      return $value;
    }

    $render_context = new RenderContext();

    $result = $this->renderer->executeInRenderContext(
      $render_context,
      fn () => $this->replaceDeep($value)
    );

    if (!$render_context->isEmpty()) {
      $context->addCacheableDependency($render_context->pop());
    }

    return $result;
  }

  /**
   * Recursively replace tokens in a value.
   *
   * @param mixed $value
   *   The value to replace tokens in.
   *
   * @return mixed
   *   The value with tokens replaced.
   */
  protected function replaceDeep($value) {
    if (is_array($value)) {
      foreach ($value as &$item) {
        $item = $this->replaceDeep($item);
      }
      return $value;
    }
    elseif (is_string($value)) {
      return $this->token->replacePlain($value);
    }
    else {
      return $value;
    }
  }

}
