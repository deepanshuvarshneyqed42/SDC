<?php

namespace Drupal\dab\Traits;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * A trait to get components data.
 *
 * @package Drupal\dab\Traits
 */
trait DabComponentTrait {

  /**
   * The components.
   *
   * @var array
   */
  protected $components;

  /**
   * The component.
   *
   * @var \Drupal\sdc\Plugin\Component
   */
  protected $component;

  /**
   * The versions.
   *
   * @var array
   */
  protected array $versions = [];

  /**
   * Get the component data.
   *
   * @param string $machine_name
   *   The component machine name.
   * @param string|null $provider
   *   The component provider.
   */
  private function getComponentData(string $machine_name, ?string $provider = NULL): void {
    if (!\Drupal::hasService('plugin.manager.sdc')) {
      return;
    }

    $componentPluginManager = \Drupal::service('plugin.manager.sdc');
    $components = [];

    try {
      $components = $componentPluginManager->getAllComponents();
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('Component @machine_name not found', ['@machine_name' => $machine_name]));
      $response = new RedirectResponse(Url::fromRoute('dab.component_type_list')->toString());
      $response->send();
    }

    $this->components = array_reduce($components, function ($acc, $component) use ($machine_name) {
      $pluginDefinition = $component->getPluginDefinition();

      if ($pluginDefinition['machineName'] === $machine_name) {
        $acc[$pluginDefinition['provider']] = $component;
      }

      return $acc;
    }, []);

    $this->component = (!is_null($provider) && array_key_exists($provider, $this->components)) ? $this->components[$provider] : reset($this->components);
    $this->getComponentVersions();
  }

  /**
   * Get the component versions.
   */
  private function getComponentVersions(): void {
    $pluginDefinition = $this->component->getPluginDefinition();
    $versions = [];

    if (!array_key_exists('props', $pluginDefinition) && !array_key_exists('properties', $pluginDefinition['props'])) {
      $this->versions = $versions;
      return;
    }

    foreach ($pluginDefinition['props']['properties'] as $propKey => $propValues) {
      if (!array_key_exists('examples', $propValues)) {
        continue;
      }

      foreach ($propValues['examples'] as $key => $example) {
        $versions[$key][$propKey] = $example;
      }
    }

    $this->versions = $versions;
  }

  /**
   * Get the template select.
   *
   * @param string|null $provider
   *   The provider of the template.
   *
   * @return array
   *   The render array of the select.
   */
  private function getTemplateSelect(?string $provider = NULL): array {
    $options = $this->getProviderOptions();

    return (count($this->components) > 1) ? [
      '#type' => 'select',
      '#id' => 'template-select',
      '#title' => $this->t('Template'),
      '#options' => $options,
      '#default_value' => $provider,
    ] : [];
  }

  /**
   * Get the provider options.
   *
   * @return array
   *   The provider options.
   */
  private function getProviderOptions() {
    return array_reduce($this->components, function ($acc, $component) {
      $pluginDefinition = $component->getPluginDefinition();
      $provider = $pluginDefinition['provider'];
      $acc[$provider] = $provider;
      return $acc;
    }, []);
  }

  /**
   * Build select version select.
   *
   * @param string|null $version
   *   The version.
   *
   * @return array
   *   The render array of the select.
   */
  private function getVersionSelect(?string $version = NULL): array {
    $options = $this->versions;
    $optionsKeys = array_reduce(array_keys($options), function ($acc, $option) {
      $acc[$option] = $option;
      return $acc;
    }, []);

    return (!empty($options) && count($options) > 1) ? [
      '#type' => 'select',
      '#id' => 'version-select',
      '#title' => $this->t('Version'),
      '#options' => $optionsKeys,
      '#value' => $version ?? reset($optionsKeys),
    ] : [];
  }

  /**
   * Get the extensions options.
   *
   * @return array
   *   The options.
   */
  private function getExtensionsOptions(): array {
    if (!\Drupal::hasService('extension.list.module') || !\Drupal::hasService('extension.list.theme')) {
      return [];
    }

    $options = [];
    $modules = \Drupal::service('extension.list.module')->getList();
    $themes = \Drupal::service('extension.list.theme')->getList();

    /** @var \Drupal\Core\Extension\Extension $module */
    foreach ($modules as $module) {
      if (preg_match('/^modules\/custom/', $module->getPath())) {
        $options['Modules'][$module->getName()] = $module->info['name'];
      }
    }

    /** @var \Drupal\Core\Extension\Extension $theme */
    foreach ($themes as $theme) {
      if (preg_match('/^themes\/custom/', $theme->getPath())) {
        $options['Themes'][$theme->getName()] = $theme->info['name'];
      }
    }

    return $options;
  }

}
