<?php

namespace Drupal\dab\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A simple form to filter components in DabComponentListController.
 */
final class ComponentFilterForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The current stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $currentRequest;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * ComponentFilterForm constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The current stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   */
  public function __construct(
    RequestStack $requestStack,
    RouteMatchInterface $routeMatch
  ) {
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'component_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['#attributes'] = [
      'class' => ['dab_form-filter'],
    ];

    $form['filter-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['dab_form-filter__container'],
      ],
    ];

    $form['filter-container']['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter by component'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->currentRequest->query->get('filter') ?: '',
      '#ajax' => [
        'callback' => '::clearFilter',
        'event' => 'search',
      ],
    ];

    $form['filter-container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#button_type' => 'primary',
      '#attributes' => [
        'title' => $this->t('Filter'),
        'class' => ['dab_form-filter__submit'],
      ],
    ];

    return $form;
  }

  /**
   * Clear the filter.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function clearFilter(array &$form, FormStateInterface $form_state) {
    $userInput = $form_state->getUserInput();
    $filter = $userInput['filter'];

    $componentType = $this->routeMatch->getParameter('component_type');
    $routeParameters = ['filter' => $filter, 'component_type' => $componentType];
    $url = Url::fromRoute($this->routeMatch->getRouteName(), $routeParameters);

    $reponse = new AjaxResponse();
    $reponse->addCommand(new RedirectCommand($url->toString()));
    return $reponse;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $filter = $form_state->getValue('filter');
    $componentType = $this->routeMatch->getParameter('component_type');
    $routeParameters = ['filter' => $filter, 'component_type' => $componentType];
    $form_state->setRedirect($this->routeMatch->getRouteName(), $routeParameters);
  }

}
