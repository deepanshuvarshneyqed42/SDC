<?php declare(strict_types = 1);

namespace Drupal\sdc_styleguide\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sdc\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Single Directory Components Styleguide form.
 */
final class SDCExplorerForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'sdc_styleguide_sdc_explorer';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(protected ComponentPluginManager $componentPluginManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sdc')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $options = [];
    $all_components = [];
    foreach ($this->componentPluginManager->getAllComponents() as $component) {
      $definition = $component->getPluginDefinition();
      $all_components[$definition['id']] = $component;
      $group_name = "{$definition['extension_type']->name}: {$definition['provider']}";
      if (!isset($options[$group_name])) {
        $options[$group_name] = [];
      }
      $options[$group_name][$definition['id']] = $definition['name'];
    }

    $form['component'] = [
      '#ajax' => [
        'callback' => '::onComponentChange',
        'event' => 'change',
        'wrapper' => 'component-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying entry...'),
        ],
      ],
      '#empty_option' => $this->t('- Select a component -'),
      '#options' => $options,
      '#title' => $this->t('Component'),
      '#type' => 'select',
    ];

    $form['component_fields'] = [
      '#attributes' => [
        'id' => 'component-wrapper',
      ],
      '#type' => 'container',
    ];

    $fapi_map = [
      'string' => 'textfield',
      'number' => 'number',
      'boolean' => 'checkbox',
    ];
    if ($form_state->getValue('component')) {
      $selected_component = $all_components[$form_state->getValue('component')];
      $definition = $selected_component->getPluginDefinition();
      $form['component_fields']['selected_component'] = [
        '#tree' => TRUE,
        'component' => [
          '#type' => 'value',
          '#value' => $form_state->getValue('component'),
        ],
      ];
      foreach ($definition['props']['required'] as $field) {
        $settings = $definition['props']['properties'][$field];
        $form['component_fields']['selected_component'][$field] = [
          '#required' => TRUE,
          '#type' => $fapi_map[$settings['type']],
          '#title' => $settings['title'],
        ];
      }
      $form['component_fields']['submit'] = [
        '#ajax' => [
          'callback' => '::onComponentSubmit',
          'event' => 'click',
          'wrapper' => 'result',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Verifying entry...'),
          ],
        ],
        '#attributes' => [
          'type' => 'button',
        ],
        '#type' => 'button',
        '#value' => 'submit',
      ];
    }

    $form['rendered_result'] = [
      '#attributes' => [
        'id' => 'result',
      ],
      '#type' => 'container',
    ];

    if ($form_state->getValue('selected_component')) {
      $component = $form_state->getValue('selected_component');
      $form['rendered_result']['component'] = [
        '#type' => 'component',
        '#component' => $component['component'],
        '#props' => $component,
      ];
      unset($form['rendered_result']['component']['#props']['component']);
    }

    return $form;
  }

  /**
   * AJAX handler for when the component selector field is changed.
   */
  public function onComponentChange(array &$form, FormStateInterface $form_state) {
    return $form['component_fields'];
  }

  /**
   * AJAX handler for when the component values are submitted.
   */
  public function onComponentSubmit(array &$form, FormStateInterface $form_state) {
    return $form['rendered_result'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }

}
