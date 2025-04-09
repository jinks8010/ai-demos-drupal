<?php

namespace Drupal\decoupled_preview_iframe\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class RedirectAnonymousSubscriber implements EventSubscriberInterface {

  /**
   * Routes to exclude from redirect.
   *
   * @var excludeRoutes[]
   */
  private $excludeRoutes = [
    'name' => [
      'user.login',
      'user.login.http',
      'user.pass',
      'user.reset',
      'user.reset.login',
      'oauth2_token.token',
    ],
    // @todo Add JSON:API routes to exclude.
    'path' => [
      '/graphql?*',
      '/sites/default/files/*',
    ],
  ];

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new RedirectAnonymousSubscriber.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path stack.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    PathMatcherInterface $path_matcher,
    CurrentPathStack $current_path,
    AliasManagerInterface $alias_manager,
    LoggerChannelFactoryInterface $logger,
    ConfigFactoryInterface $config_factory,
    RouteMatchInterface $current_route_match,
  ) {
    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
    $this->currentPath = $current_path;
    $this->aliasManager = $alias_manager;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * Check if current route is excluded from redirect.
   *
   * @return bool
   *   TRUE if route is excluded, FALSE otherwise.
   */
  private function shouldRedirect(
    $routeName,
    $currentPath,
    $currentPathAlias,
  ) {
    $isValidByName = in_array($routeName, $this->excludeRoutes['name']);

    if ($isValidByName) {
      return FALSE;
    }

    $patterns = $this->excludeRoutes['path'];
    foreach ($patterns as $pattern) {
      if ($this->pathMatcher->matchPath($currentPath, $pattern)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Implements function redirect().
   */
  public function redirect(RequestEvent $event) {
    $config = $this->configFactory->get('decoupled_preview_iframe.settings');

    if (PHP_SAPI === 'cli') {
      return;
    }

    if (!$this->currentUser->isAnonymous()) {
      return;
    }

    if (!boolval($config->get('redirect_anonymous'))) {
      return;
    }

    $routeName = $this->currentRouteMatch->getRouteName();
    $currentPath = $this->currentPath->getPath();
    $currentPathAlias = $this->aliasManager->getAliasByPath($currentPath);

    if (substr($currentPathAlias, -4) == '.php') {
      return;
    }

    if ($this->shouldRedirect($routeName, $currentPath, $currentPathAlias)) {
      $request = $event->getRequest();
      $query = '';
      if ($request->query->count()) {
        $query = '?' . str_replace('&', '%26', $request->getQueryString());
      }

      $redirect_url = $config->get('redirect_url');

      $redirectUrl = $redirect_url . $currentPathAlias . $query;
      $this->logger->get('decoupled_preview_iframe')->error(
        "Route: '{route_name}', Path: '{current_path}', " .
        "Alias: '{current_path_alias}' not allowed, " .
        "Redirecting to: '{redirect_url}' url",
        [
          'route_name' => $routeName,
          'current_path' => $currentPath,
          'current_path_alias' => $currentPathAlias,
          'redirect_url' => $redirectUrl,
        ]
      );

      $event->setResponse(new TrustedRedirectResponse($redirectUrl, 302));
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['redirect', 30];

    return $events;
  }

}
