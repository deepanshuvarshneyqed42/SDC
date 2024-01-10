<?php

namespace Drupal\dab\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Plugin\Component;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for component menu links.
 */
final class AtomicComponentMenuLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The component plugin manager.
   *
   * @var \Drupal\sdc\ComponentPluginManager
   */
  private $componentPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.sdc')
    );
  }

  /**
   * AtomicComponentMenuLinkDeriver constructor.
   *
   * @param \Drupal\sdc\ComponentPluginManager $componentPluginManager
   *   The component plugin manager.
   */
  public function __construct(ComponentPluginManager $componentPluginManager) {
    $this->componentPluginManager = $componentPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];
    $components = $this->componentPluginManager->getAllComponents();

    foreach ($components as $component) {
      $pluginDefinition = $component->getPluginDefinition();
      $group = $pluginDefinition['group'] ?? 'other';

      // Component type.
      $componentTypeId = "dab.components:{$group}";
      $links[$componentTypeId] = [
        'id' => $componentTypeId,
        'title' => $this->t('@component_type', ['@component_type' => ucfirst($group)]),
        'parent' => 'dab.menu',
        'route_name' => 'dab.component_type_list',
        'route_parameters' => ['component_type' => $group],
      ] + $base_plugin_definition;

      // Add component.
      $componentAddTypeId = "dab.components:add_{$group}";
      $links[$componentAddTypeId] = [
        'id' => $componentTypeId,
        'title' => $this->t('Add @component_type', ['@component_type' => rtrim($group, 's')]),
        'parent' => "dab.components:{$componentTypeId}",
        'route_name' => 'dab.add_component',
        'route_parameters' => ['component_type' => $group],
        'weight' => -50,
      ] + $base_plugin_definition;

      $this->buildComponentMenuItem(
        $base_plugin_definition,
        $links,
        $component,
        $group,
        $componentTypeId
      );
    }

    return $links;
  }

  /**
   * The component menu item.
   *
   * @param mixed $base_plugin_definition
   *   The base plugin ddefinition.
   * @param array $links
   *   The links array.
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   * @param string $group
   *   The component type name.
   * @param string $parent
   *   The parent id.
   */
  private function buildComponentMenuItem(
    $base_plugin_definition,
    array &$links,
    Component $component,
    string $group,
    string $parent,
  ) {
    $componentPluginDefinition = $component->getPluginDefinition();
    $pluginId = $component->getPluginId();
    $provider = $componentPluginDefinition['provider'];
    $componentName = $componentPluginDefinition['name'] . " ({$pluginId})";
    $machineName = $component->machineName;

    // Component.
    $id = "dab.components:{$provider}_{$machineName}";
    $links[$id] = [
      'id' => $id,
      'parent' => "dab.components:{$parent}",
      'title' => $componentName,
      'route_name' => 'dab.component',
      'route_parameters' => [
        'component_type' => $group,
        'machine_name' => $machineName,
        'provider' => $provider,
      ],
    ] + $base_plugin_definition;

    // Component Tool.
    $deleteId = "dab.delete_component:{$provider}_{$machineName}";
    $links[$deleteId] = [
      'id' => $deleteId,
      'parent' => "dab.components:{$id}",
      'title' => $this->t('Delete'),
      'route_name' => 'dab.delete_component',
      'route_parameters' => [
        'component_type' => $group,
        'machine_name' => $machineName,
        'provider' => $provider,
      ],
      'weight' => 50,
    ] + $base_plugin_definition;

    $editId = "dab.edit_component:{$provider}_{$machineName}";
    $links[$editId] = [
      'id' => $editId,
      'parent' => "dab.components:{$id}",
      'title' => $this->t('Edit'),
      'route_name' => 'dab.edit_component',
      'route_parameters' => [
        'component_type' => $group,
        'machine_name' => $machineName,
        'provider' => $provider,
      ],
      'weight' => 1,
    ] + $base_plugin_definition;

    $duplicateId = "dab.duplicate_component:{$provider}_{$machineName}";
    $links[$duplicateId] = [
      'id' => $duplicateId,
      'parent' => "dab.components:{$id}",
      'title' => $this->t('Duplicate'),
      'route_name' => 'dab.duplicate_component',
      'route_parameters' => [
        'component_type' => $group,
        'machine_name' => $machineName,
        'provider' => $provider,
      ],
      'weight' => 2,
    ] + $base_plugin_definition;
  }

}
