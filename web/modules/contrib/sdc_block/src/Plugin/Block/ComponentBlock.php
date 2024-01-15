<?php

namespace Drupal\sdc_block\Plugin\Block;

use Drupal\cl_editorial\Form\ComponentInputToForm;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Plugin\Component;
use Drupal\sdc_block\Render\ComponentBlockRenderer;
use Psr\Log\LoggerInterface;
use SchemaForms\FormGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that renders a component.
 *
 * @phpstan-consistent-constructor
 * @Block(
 *   id = "sdc_component_block",
 *   deriver = "\Drupal\sdc_block\Plugin\Derivative\ComponentBlockDeriver",
 *   context_definitions = {
 *     "language" = @ContextDefinition(
 *       "language",
 *       required = FALSE,
 *       label = @Translation("Language")
 *     ),
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       required = FALSE,
 *       label = @Translation("Node")
 *     ),
 *     "user" = @ContextDefinition(
 *       "entity:user",
 *       required = FALSE,
 *       label = @Translation("User Context"),
 *       constraints = { "NotNull" = {} },
 *     ),
 *   }
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class ComponentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The component.
   *
   * @var \Drupal\sdc\Plugin\Component
   */
  protected Component $component;

  /**
   * Constructs a new FieldBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\sdc_block\Render\ComponentBlockRenderer $renderer
   *   The block renderer.
   * @param \Drupal\sdc\ComponentPluginManager $componentPluginManager
   *   The component plugin manager.
   * @param \SchemaForms\FormGeneratorInterface $formGenerator
   *   The form generator.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerInterface $logger,
    protected ComponentBlockRenderer $renderer,
    protected ComponentPluginManager $componentPluginManager,
    protected FormGeneratorInterface $formGenerator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Get the entity type and field name from the plugin ID.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.sdc_block'),
      $container->get(ComponentBlockRenderer::class),
      $container->get('plugin.manager.sdc'),
      $container->get('cl_editorial.form_generator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $props_config = $config['component']['props'] ?? [];
    $slots_config = $config['component']['slots'] ?? [];
    return $this->renderer->buildFromId(
      $this->getPluginDefinition()['component_id'],
      $props_config,
      $slots_config,
      array_filter($this->getContextValues()),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewFallbackString() {
    return new TranslatableMarkup(
      '"@component" Component',
      ['@component' => $this->getPluginDefinition()['component_id']]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'component' => [
        'props' => [],
        'slots' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $component_to_form = new ComponentInputToForm(
      $this->componentPluginManager,
      $this->formGenerator,
    );
    $form['component'] = $component_to_form->buildForm(
      $this->getPluginDefinition()['component_id'],
      array_intersect_key($config['component'], array_flip(['props', 'slots'])),
      $form,
      $form_state,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['component'] = $form_state->getValue('component');
  }

}
