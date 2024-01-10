<?php

namespace Drupal\dab\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\dab\Service\ComponentFileManager;
use Drupal\dab\Traits\DabComponentTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Depending on parameter, this form will delete/duplication a component.
 */
final class ConfirmationComponentForm extends FormBase {

  use DabComponentTrait;

  /**
   * The form id.
   *
   * @var string
   */
  const FORM_ID = 'dab_confirmation_component_form';

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The component file manager service.
   *
   * @var \Drupal\dab\Service\ComponentFileManager
   */
  protected ComponentFileManager $componentFileManager;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\dab\Service\ComponentFileManager $component_file_manager
   *   The component file manager service.
   */
  public function __construct(
    MessengerInterface $messenger,
    FileSystemInterface $file_system,
    ComponentFileManager $component_file_manager
  ) {
    $this->messenger = $messenger;
    $this->fileSystem = $file_system;
    $this->componentFileManager = $component_file_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('file_system'),
      $container->get('dab.component_file_manager')
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
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?string $component_type = NULL,
    ?string $machine_name = NULL,
    ?string $action = NULL,
    ?string $provider = NULL
  ) {
    if (
      empty($machine_name)
      || empty($component_type)
      || empty($action) ||
      !in_array($action, ['delete', 'duplicate'])
    ) {
      throw new NotFoundHttpException();
    }

    $this->getComponentData($machine_name);

    $form['form_action'] = [
      '#type' => 'hidden',
      '#value' => $action,
    ];

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="">' .
      $this->t(
        'Do you really want to @action the <strong>@machine_name</strong> component ?',
        ['@machine_name' => $machine_name, '@action' => $action])
      . '</div>',
    ];

    $form['origin'] = $this->getTemplateSelect($provider);

    if (!empty($form['origin']) && $action === 'delete') {
      $form['origin']['#type'] = 'checkboxes';
    }

    if ($action === 'duplicate') {
      $form['provider'] = [
        '#type' => 'select',
        '#title' => $this->t('Provider'),
        '#options' => $this->getExtensionsOptions(),
        '#default_value' => $provider,
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
      '#button_type' => $action === 'delete' ? 'danger' : 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('dab.component', [
        'component_type' => $component_type,
        'machine_name' => $machine_name,
        'provider' => $provider,
      ]),
      '#title' => $this->t('Cancel'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getValue('form_action');
    $origin = $form_state->getValue('origin');
    $origins = is_array($origin) ? $origin : [$origin];

    foreach ($origins as $origin) {
      if (!array_key_exists($origin, $this->components)) {
        continue;
      }

      $component = $this->components[$origin];
      $pluginDefinition = $component->getPluginDefinition();
      $path = $pluginDefinition['path'];
      $newProvider = $form_state->getValue('provider');
      $isActionSuccessfull = $action === 'delete'
        ? $this->fileSystem->deleteRecursive($path)
        : $this->componentFileManager->duplicateComponent($component, $newProvider);

      if ($isActionSuccessfull) {
        $message = $action === 'delete'
          ? $this->t('The component @machine_name has been deleted.', [
            '@machine_name' => $this->component->machineName,
          ])
          : $this->t('The component @machine_name has been duplicated in @path.', [
            '@machine_name' => $this->component->machineName,
            '@path' => $newProvider,
          ]);
        $this->messenger->addMessage($message);
        $form_state->setRedirectUrl(Url::fromRoute('dab.component_type_list'));
      }
      else {
        $this->messenger->addError($this->t(
          'An error occurred on component action : @action.',
          ['@action' => $action])
        );
      }
    }

    drupal_flush_all_caches();

  }

}
