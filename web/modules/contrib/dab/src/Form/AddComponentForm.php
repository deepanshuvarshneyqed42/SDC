<?php

namespace Drupal\dab\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dab\Service\ComponentFileManager;
use Drupal\dab\Traits\DabComponentTrait;
use Drupal\sdc\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configure Drupal Atomic Builder add_component.
 */
final class AddComponentForm extends FormBase implements ContainerInjectionInterface {

  use DabComponentTrait;

  /**
   * The form id.
   *
   * @var string
   */
  const FORM_ID = 'dab_add_component';

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected ThemeExtensionList $themeExtensionList;

  /**
   * The component plugin manager.
   *
   * @var \Drupal\sdc\ComponentPluginManager
   */
  protected ComponentPluginManager $componentPluginManager;

  /**
   * The component file manager.
   *
   * @var \Drupal\dab\Service\ComponentFileManager
   */
  protected ComponentFileManager $componentFileManager;

  /**
   * The provider path.
   *
   * @var string
   */
  private string $providerPath;

  /**
   * True if we are on editing mode.
   *
   * @var bool
   */
  private bool $isEdit = FALSE;

  /**
   * AddComponentForm constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_extension_list
   *   The theme extension service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\sdc\ComponentPluginManager $component_plugin_manager
   *   The component plugin manager.
   * @param \Drupal\dab\Service\ComponentFileManager $component_file_manager
   *   The component file manager.
   */
  public function __construct(
    ModuleExtensionList $module_extension_list,
    ThemeExtensionList $theme_extension_list,
    RequestStack $request_stack,
    ComponentPluginManager $component_plugin_manager,
    ComponentFileManager $component_file_manager
  ) {
    $this->moduleExtensionList = $module_extension_list;
    $this->themeExtensionList = $theme_extension_list;
    $this->requestStack = $request_stack;
    $this->componentPluginManager = $component_plugin_manager;
    $this->componentFileManager = $component_file_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
      $container->get('request_stack'),
      $container->get('plugin.manager.sdc'),
      $container->get('dab.component_file_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $component_type = NULL, ?string $machine_name = NULL) {
    $form['#tree'] = TRUE;
    $currentRequest = $this->requestStack->getCurrentRequest();
    $componentType = $currentRequest->get('component_type') ?? '';
    $this->isEdit = !is_null($component_type) && !is_null($machine_name);
    $componentPluginDefinition = [];
    $defaultValues = [
      'name' => '',
      'machine_name' => $machine_name,
      'group' => $componentType,
      'description' => '',
      'provider' => '',
    ];

    if ($this->isEdit) {
      $this->getComponentData($machine_name);
      $componentPluginDefinition = $this->component->getPluginDefinition();
      $defaultValues = [
        'name' => $componentPluginDefinition['name'],
        'machine_name' => $machine_name,
        'group' => $componentPluginDefinition['group'],
        'description' => $componentPluginDefinition['description'],
        'provider' => $componentPluginDefinition['provider'],
      ];
    }

    $form['yaml']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Component name'),
      '#description' => $this->t('Please use only letters, space or underscore'),
      '#required' => TRUE,
      '#default_value' => $defaultValues['name'],
    ];

    $form['yaml']['machine_name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => function ($value) use ($form_state) {
          return $this->exists($value, $form_state);
        },
        'source' => ['yaml', 'name'],
      ],
      '#default_value' => $defaultValues['machine_name'],
    ];

    $form['yaml']['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Component type'),
      '#description' => $this->t('Equivalent to the group entry in the .component.yml file.'),
      '#options' => $this->getComponentTypesOptions(),
      '#default_value' => $defaultValues['group'],
    ];

    $form['yaml']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Enter a description for the field.'),
      '#default_value' => $defaultValues['description'],
    ];

    $form['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Provider'),
      '#options' => $this->getExtensionsOptions(),
      '#default_value' => $defaultValues['provider'],
    ];

    $assetExtensions = ['js', 'css'];

    foreach ($assetExtensions as $extension) {
      $hasAsset = !!$componentPluginDefinition['library'][$extension];
      $isDeleteAsset = $this->isEdit && $hasAsset;
      $fieldId = $isDeleteAsset ? "delete_$extension" : "add_$extension";
      $title = $isDeleteAsset
        ? $this->t("Remove @extension to your component", ['@extension' => $extension])
        : $this->t("Add @extension to your component", ['@extension' => $extension]);

      $form[$fieldId] = [
        '#type' => 'checkbox',
        '#title' => $title,
        '#default_value' => FALSE,
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Check if the component's machine name exists.
   *
   * @param string $value
   *   The value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   The result.
   */
  protected function exists(string $value, FormStateInterface $form_state) {
    $inputs = $form_state->getUserInput();
    $provider = $inputs['provider'] ?? NULL;

    try {
      $this->componentPluginManager->find("$provider:$value");
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get the component types options.
   *
   * @return array
   *   The options.
   */
  protected function getComponentTypesOptions() {
    $options = [
      'atoms' => $this->t('Atoms'),
      'molecules' => $this->t('Molecules'),
      'organisms' => $this->t('Organisms'),
      'templates' => $this->t('Templates'),
      'pages' => $this->t('Pages'),
      'other' => $this->t('Other'),
    ];

    $componentTypeFormConfig = $this->config(ConfigureComponentsTypesForm::CONFIG_NAME);
    /** @var string $componentTypes */
    $componentTypes = $componentTypeFormConfig->get('component_types');

    if (!empty($componentTypes)) {
      $componentTypes = trim($componentTypes);
      $componentTypes = explode("\r\n", $componentTypes);
      $options = array_reduce($componentTypes, function ($acc, $componentType) {
        $componentType = explode('|', $componentType);
        $acc[$componentType[0]] = $componentType[1];
        return $acc;
      }, []);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('yaml');
    $machineName = $values['machine_name'];
    $provider = $form_state->getValue('provider');

    if (preg_match('/^[a-z]+_?[a-z]*$/', $machineName) != 1) {
      $form_state->setErrorByName('machine_name', $this->t('Must only be lowercase letters with underscore.<br>Example: machine_name'));
    }

    $componentPath = "/components/$machineName";
    $componentModulePath = $this->moduleExtensionList->exists($provider) ? $this->moduleExtensionList->getPath($provider) : NULL;
    $componentThemePath = $this->themeExtensionList->exists($provider) ? $this->themeExtensionList->getPath($provider) : NULL;
    $this->providerPath = $componentModulePath ?? $componentThemePath;

    if (is_null($this->providerPath)) {
      $form_state->setErrorByName('provider', $this->t('The provider @provider does not exist.', ['@provider' => $provider]));
    }

    if ((is_dir($componentModulePath . $componentPath) || is_dir($componentThemePath . $componentPath)) && !$this->isEdit) {
      $form_state->setErrorByName('machine_name', $this->t('The machine name @machine_name already exists for this provider @provider.', [
        '@machine_name' => $machineName,
        '@provider' => $provider,
      ]));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($this->isEdit) {
      $result = $this->updateComponent($values);
    }
    else {
      $result = $this->createComponent($values);
    }

    drupal_flush_all_caches();

    if ($result) {
      $this->messenger()->addStatus($this->t(
        'The component @machine_name has been @action successfully.',
        [
          '@machine_name' => $values['yaml']['machine_name'],
          '@action' => $this->isEdit ? 'updated' : 'created',
        ]
      ));
      $form_state->setRedirectUrl(Url::fromRoute('dab.component', [
        'component_type' => $values['yaml']['group'] ?? 'other',
        'machine_name' => $values['yaml']['machine_name'],
        'provider' => $values['provider'],
      ]));
    }
    else {
      $this->messenger()->addError($this->t(
        'There was an error during the @action of @machine_name',
        [
          '@machine_name' => $values['yaml']['machine_name'],
          '@action' => $this->isEdit ? 'update' : 'creation',
        ],
      ));
    }
  }

  /**
   * Create the component.
   *
   * @param array $values
   *   The values.
   *
   * @return bool
   *   The result.
   */
  private function createComponent(array $values): bool {
    $yamlValues = $values['yaml'];
    $this->componentFileManager->createComponentFolder(
      $yamlValues['machine_name'],
      $this->providerPath,
      $yamlValues['group']
    );
    // Component file.
    $this->componentFileManager->createComponentFile(
      $yamlValues['machine_name'],
      $this->providerPath,
      $yamlValues['name'],
      $yamlValues['group'],
      $yamlValues['description']
    );
    // Readme file.
    $this->componentFileManager->createReadmeFile(
      $yamlValues['machine_name'],
      $this->providerPath,
      $yamlValues['name'],
      $yamlValues['description'],
      $yamlValues['group']
    );
    // Twig file.
    $this->componentFileManager->createTwigFile(
      $yamlValues['machine_name'],
      $this->providerPath,
      $yamlValues['group']
    );

    // Js file.
    if ($values['add_js']) {
      $this->componentFileManager->createJsFile(
        $yamlValues['machine_name'],
        $this->providerPath,
        $yamlValues['group']
      );
    }

    // Css file.
    if ($values['add_css']) {
      $this->componentFileManager->createCssFile(
        $yamlValues['machine_name'],
        $this->providerPath,
        $yamlValues['group']
      );
    }

    return TRUE;
  }

  /**
   * Update the component.
   *
   * @param array $values
   *   The values.
   *
   * @return bool
   *   The result.
   */
  private function updateComponent(array $values): bool {
    // Update the component file.
    $componentFile = $this->componentFileManager->loadComponentFile($this->component);

    foreach ($values['yaml'] as $property => $value) {
      if (empty($value) || $property === 'machine_name') {
        continue;
      }

      $componentFile[$property] = $value;
    }

    $this->componentFileManager->saveComponentFile($this->component, $componentFile);

    // Créer ou supprimer CSS/JS.
    $assets = ['js', 'css'];

    foreach ($assets as $asset) {
      $upAsset = ucfirst($asset);
      $createMethodName = "create{$upAsset}File";

      if ($values["add_$asset"]) {
        $this->componentFileManager->$createMethodName(
          $values['yaml']['machine_name'],
          $values['provider'],
          $values['yaml']['group']
        );
      }

      if ($values["delete_$asset"]) {
        $this->componentFileManager->deleteComponentFile($this->component, $asset);
      }
    }

    // If provider or machine name is changed -> move the component folder.
    $this->componentFileManager->moveComponentFolder(
      $this->component,
      $values['yaml']['machine_name'],
      $values['provider'],
      $values['yaml']['group']
    );
    return TRUE;
  }

}
