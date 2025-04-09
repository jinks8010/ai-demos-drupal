<?php

namespace Drupal\navigation_extra_tools\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver class to add extra links to the navigation menus.
 */
class ToolsMenuDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected RouteProviderInterface $routeProvider,
    protected ThemeHandlerInterface $themeHandler,
    protected ConfigFactoryInterface $configFactory,
    protected AccountInterface $currentUser,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('router.route_provider'),
      $container->get('theme_handler'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    // If module Devel is enabled.
    if ($this->moduleHandler->moduleExists('devel')) {
      $links['devel'] = [
        'title' => $this->t('Development'),
        'route_name' => 'system.admin_config_development',
        'parent' => 'navigation_extra_tools.help',
        'weight' => '-8',
      ] + $base_plugin_definition;
      $links['devel.admin_settings'] = [
        'title' => $this->t('Devel settings'),
        'route_name' => 'devel.admin_settings',
        'parent' => $base_plugin_definition['id'] . ':devel',
        'weight' => '-31',
      ] + $base_plugin_definition;
      $links['devel.configs_list'] = [
        'title' => $this->t('Config editor'),
        'route_name' => 'devel.configs_list',
        'parent' => $base_plugin_definition['id'] . ':devel',
        'weight' => '-30',
      ] + $base_plugin_definition;
      $links['devel.reinstall'] = [
        'title' => $this->t('Reinstall modules'),
        'route_name' => 'devel.reinstall',
        'parent' => $base_plugin_definition['id'] . ':devel',
        'weight' => '-29',
      ] + $base_plugin_definition;
      $links['devel.menu_rebuild'] = [
        'title' => $this->t('Rebuild menu'),
        'route_name' => 'devel.menu_rebuild',
        'parent' => $base_plugin_definition['id'] . ':devel',
        'weight' => '-28',
      ] + $base_plugin_definition;
      $links['devel.state_system_page'] = [
        'title' => $this->t('State editor'),
        'route_name' => 'devel.state_system_page',
        'parent' => $base_plugin_definition['id'] . ':devel',
        'weight' => '-27',
      ] + $base_plugin_definition;
      $links['devel.theme_registry'] = [
        'title' => $this->t('Theme registry'),
        'route_name' => 'devel.theme_registry',
        'parent' => $base_plugin_definition['id'] . ':devel',
        'weight' => '-26',
      ] + $base_plugin_definition;
      $links['devel.entity_info_page'] = [
        'title' => $this->t('Entity info'),
        'route_name' => 'devel.entity_info_page',
        'parent' => $base_plugin_definition['id'] . ':devel',
        'weight' => '-25',
      ] + $base_plugin_definition;
      $links['devel.session'] = [
        'title' => $this->t('Session viewer'),
        'route_name' => 'devel.session',
        'parent' => $base_plugin_definition['id'] . ':devel',
        'weight' => '-24',
      ] + $base_plugin_definition;
      $links['devel.element_info'] = [
        'title' => $this->t('Element Info'),
        'route_name' => 'devel.elements_page',
        'parent' => $base_plugin_definition['id'] . ':devel',
        'weight' => '-23',
      ] + $base_plugin_definition;
      // Menu link for the Toolbar module.
      $links['devel.toolbar.settings'] = [
        'title' => $this->t('Devel Toolbar Settings'),
        'route_name' => 'devel.toolbar.settings_form',
        'parent' => $base_plugin_definition['id'] . ':devel',
        'weight' => '-22',
      ] + $base_plugin_definition;
      if ($this->moduleHandler->moduleExists('webprofiler')) {
        $links['devel.webprofiler'] = [
          'title' => $this->t('Webprofiler settings'),
          'route_name' => 'webprofiler.settings',
          'parent' => $base_plugin_definition['id'] . ':devel',
          'weight' => '-21',
        ] + $base_plugin_definition;
      }
      // If module Devel PHP is enabled.
      if ($this->moduleHandler->moduleExists('devel_php') && $this->routeExists('devel_php.execute_php')) {
        $links['devel.devel_php.execute_php'] = [
          'title' => $this->t('Execute PHP Code'),
          'route_name' => 'devel_php.execute_php',
          'parent' => $base_plugin_definition['id'] . ':devel',
        ] + $base_plugin_definition;
      }
    }

    return $links;
  }

  /**
   * Determine if a route exists by name.
   *
   * @param string $route_name
   *   The name of the route to check.
   *
   * @return bool
   *   Whether a route with that route name exists.
   */
  public function routeExists($route_name) {
    return (count($this->routeProvider->getRoutesByNames([$route_name])) === 1);
  }

}
