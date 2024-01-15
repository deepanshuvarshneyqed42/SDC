<?php

namespace Drupal\sdc_block\Plugin\Derivative;

use Drupal\cl_editorial\NoThemeComponentManager;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sdc\Plugin\Component;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides component block definitions for every field.
 *
 * @phpstan-consistent-constructor
 * @internal
 *   Plugin derivers are internal.
 */
class ComponentBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Create a new object.
   *
   * @param \Drupal\cl_editorial\NoThemeComponentManager $componentManager
   *   A component manager that does not take a theme into account.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(protected readonly NoThemeComponentManager $componentManager, protected readonly ConfigFactoryInterface $configFactory) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $service_ids = [NoThemeComponentManager::class, 'config.factory'];
    return new static(...array_map([$container, 'get'], $service_ids));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $filters = $this->configFactory
      ->get('sdc_tags.settings')
      ->get(sprintf('component_tags.sdc_block:block')) ?? [];
    unset($filters['tag_id']);
    $components = $this->componentManager->getFilteredComponents(...$filters);
    $this->derivatives = array_reduce(
      $components,
      function (array $carry, Component $component) use ($base_plugin_definition) {
        $definition = $base_plugin_definition;
        $definition['admin_label'] = empty($definition['admin_label']) ? $component->metadata->name : $definition['admin_label'];
        $definition['category'] = $definition['category'] ?? $this->t('Components');
        $definition['component_id'] = $component->getPluginId();
        $carry[$component->getPluginId()] = $definition;
        return $carry;
      },
      []
    );
    return $this->derivatives;
  }

}
