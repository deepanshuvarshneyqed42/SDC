<?php

namespace Drupal\dab\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\dab\Form\ComponentFilterForm;
use Drupal\sdc\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines DabComponentListController class.
 */
final class DabComponentListController extends ControllerBase {

  /**
   * The component plugin manager.
   *
   * @var \Drupal\sdc\ComponentPluginManager
   */
  protected $componentPluginManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The current stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * DabComponentListController constructor.
   */
  public function __construct(
    ComponentPluginManager $componentPluginManager,
    FormBuilderInterface $formBuilder,
    RequestStack $requestStack
  ) {
    $this->componentPluginManager = $componentPluginManager;
    $this->formBuilder = $formBuilder;
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sdc'),
      $container->get('form_builder'),
      $container->get('request_stack')
    );
  }

  /**
   * Returns the title for the component list.
   *
   * @param string|null $component_type
   *   The component type.
   *
   * @return string
   *   TThe title.
   */
  public function getTitle($component_type = NULL) {
    return is_null($component_type)
      ? 'Drupal Atomic Builder'
      : $this->t(
        '@component_type',
        ['@component_type' => ucfirst($component_type)]
      );
  }

  /**
   * Build the page data.
   *
   * @return array
   *   Return template and data.
   */
  public function build($component_type = NULL) {
    $componentList = [];
    $allComponents = [];

    $filter = $this->currentRequest->query->get('filter') ?: '';

    try {
      $components = $this->componentPluginManager->getAllComponents();
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
      return [];
    }

    /** @var \Drupal\sdc\Plugin\Component $component */
    foreach ($components as $component) {
      $pluginDefinition = $component->getPluginDefinition();
      $componentName = $pluginDefinition['machineName'];
      $pluginId = $component->getPluginId();
      $group = $pluginDefinition['group'] ?? 'other';

      if (!isset($componentList[$group])) {
        $componentList[$group] = [
          'title' => ucfirst($group),
          'url' => Url::fromRoute('dab.component_type_list', ['component_type' => $group]),
          'class' => [
            ...($component_type === $group ? ['active'] : []),
          ],
        ];
      }

      if (!empty($filter) && strpos($componentName, $filter) === FALSE) {
        continue;
      }

      $componentList[$group]['components'][$pluginId] = [
        'title' => ($pluginDefinition['name'] ?? ucfirst($componentName)),
        'machine_name' => $pluginId,
        'description' => $pluginDefinition['description'] ?? '',
        'url' => Url::fromRoute('dab.component', [
          'component_type' => $group,
          'machine_name' => $componentName,
          'provider' => $pluginDefinition['provider'],
        ]),
      ];

      $allComponents[$pluginId] = $componentList[$group]['components'][$pluginId];
    }

    ksort($componentList);

    return [
      '#theme' => 'dab_component_list',
      '#components_types' => $componentList,
      '#components' => empty($component_type) ? $allComponents : $componentList[$component_type]['components'],
      '#form' => $this->formBuilder->getForm(ComponentFilterForm::class),
      '#attached' => [
        'library' => [
          'dab/global',
        ],
      ],
    ];
  }

}
