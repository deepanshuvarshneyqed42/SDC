<?php

namespace Drupal\sdc_block\Render;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Utility\Token;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Exception\ComponentNotFoundException;
use Drupal\sdc\Plugin\Component;

/**
 * Renders blocks for components.
 */
class ComponentBlockRenderer {

  /**
   * Constructs ComponentBlockRenderer classes.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\sdc\ComponentPluginManager $pluginManager
   *   The component plugin manager.
   */
  public function __construct(
    protected readonly Token $token,
    protected readonly ComponentPluginManager $pluginManager
  ) {}

  /**
   * Builds a component with all the necessary context for a block.
   *
   * @param string $component_id
   *   The component ID.
   * @param array $props_config
   *   The props stored in the block config.
   * @param array $slots_config
   *   The slots stored in the block config.
   * @param array $token_data
   *   The token data for token replacements.
   *
   * @return array
   *   The output render array.
   */
  public function buildFromId(
    string $component_id,
    array $props_config,
    array $slots_config,
    array $token_data = []
  ): array {
    try {
      $component = $this->pluginManager->find($component_id);
    }
    catch (ComponentNotFoundException $e) {
      return ['#markup' => ''];
    }
    return $this->build(
      $component,
      $props_config,
      $slots_config,
      $token_data
    );
  }

  /**
   * Renders a component with all the necessary context for a block.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   * @param array $props_config
   *   The props stored in the block config.
   * @param array $slots_config
   *   The slots stored in the block config.
   * @param array $token_data
   *   The token data for token replacements.
   *
   * @return array
   *   The output render array.
   */
  public function build(
    Component $component,
    array $props_config,
    array $slots_config,
    array $token_data = []
  ): array {
    $token_options = ['clear' => TRUE];
    // If there is a language in the context of the block, use it.
    $language = $token_data['language'] ?? NULL;
    if ($language instanceof LanguageInterface) {
      $token_options['langcode'] = $language->getId();
    }
    $props_alter_callback = fn(mixed $data) => $this->replaceTokensRecursively(
      $data,
      $token_data,
      $token_options,
    );
    $slots_alter_callback = fn(mixed $data) => array_merge(
      $data,
      [
        '#text' => $this->token->replace($data['#text'], $token_data, $token_options),
      ],
    );
    return [
      '#type' => 'component',
      '#component' => $component->getPluginId(),
      '#slots' => array_map(
        static fn(array $fixed_value) => [
          '#type' => 'processed_text',
          '#text' => $fixed_value['value'],
          '#format' => $fixed_value['format'] ?? 'plain_text',
        ],
        $slots_config,
      ),
      '#props' => $props_config,
      '#propsAlter' => [$props_alter_callback],
      '#slotsAlter' => [$slots_alter_callback],
    ];
  }

  /**
   * Replaces tokens recursively in a data structure.
   *
   * @param array $context
   *   The context.
   * @param array $token_data
   *   The token data.
   * @param array $token_options
   *   The options for token replacement.
   * @param string $replacement_type
   *   Either 'plain' or 'html' to force replacing as plain text or not.
   *
   * @return array
   *   The context with the tokens replaced.
   */
  private function replaceTokensRecursively(array $context, array $token_data, array $token_options, string $replacement_type = 'plain'): array {
    return array_map(
      function (mixed $item) use ($token_data, $token_options, $replacement_type): mixed {
        if (is_string($item)) {
          $replacement_args = [
            $item,
            $token_data,
            $token_options,
          ];
          return $replacement_type === 'plain'
            ? call_user_func_array([$this->token, 'replacePlain'], $replacement_args)
            : call_user_func_array([$this->token, 'replace'], $replacement_args);
        }
        if (is_array($item)) {
          return $this->replaceTokensRecursively($item, $token_data, $token_options, $replacement_type);
        }
        return $item;
      },
      $context
    );
  }

}
