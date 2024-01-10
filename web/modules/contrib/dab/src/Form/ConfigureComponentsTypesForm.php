<?php

namespace Drupal\dab\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Components Types form.
 */
class ConfigureComponentsTypesForm extends ConfigFormBase {

  /**
   * The config name.
   *
   * @var string
   */
  public const CONFIG_NAME = 'dab.component_type.config';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dab_component_type_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);

    $form['component_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Component Types'),
      '#default_value' => $config->get('component_types'),
      '#description' => $this->t('Enter one component type per line in the format: machine_name|Label'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config(self::CONFIG_NAME)
      ->set('component_types', $values['component_types'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
