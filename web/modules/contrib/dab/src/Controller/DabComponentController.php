<?php

namespace Drupal\dab\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Url;
use Drupal\dab\Form\CacheClearForm;
use Drupal\dab\Service\ComponentFileManager;
use Drupal\dab\Service\MarkdownService;
use Drupal\dab\Traits\DabComponentTrait;
use Symfony\Component\DependencyInjection\ContainerInterface as DependencyInjectionContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Drupal Atomic Builder routes.
 */
final class DabComponentController extends ControllerBase {

  use DabComponentTrait;

  /**
   * The twig service.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected TwigEnvironment $twig;

  /**
   * The request service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $request;

  /**
   * The markdown service.
   *
   * @var \Drupal\dab\Service\MarkdownService
   */
  protected MarkdownService $markdown;

  /**
   * The component file manager service.
   *
   * @var \Drupal\dab\Service\ComponentFileManager
   */
  protected ComponentFileManager $componentFile;

  /**
   * The construct method.
   *
   * @param \Drupal\Core\Template\TwigEnvironment $twig_service
   *   The twig service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\dab\Service\MarkdownService $markdown_service
   *   The markdown service.
   * @param \Drupal\dab\Service\ComponentFileManager $component_file_manager
   *   The component file manager service.
   */
  public function __construct(
    TwigEnvironment $twig_service,
    FormBuilderInterface $form_builder,
    RequestStack $request_stack,
    MarkdownService $markdown_service,
    ComponentFileManager $component_file_manager
  ) {
    $this->twig = $twig_service;
    $this->formBuilder = $form_builder;
    $this->request = $request_stack;
    $this->markdown = $markdown_service;
    $this->componentFile = $component_file_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(DependencyInjectionContainerInterface $container) {
    return new static(
      $container->get('twig'),
      $container->get('form_builder'),
      $container->get('request_stack'),
      $container->get('dab.markdown_service'),
      $container->get('dab.component_file_manager')
    );
  }

  /**
   * Build the controller title.
   *
   * @param string $component_type
   *   The component type if given.
   * @param string $machine_name
   *   The component name if given.
   *
   * @return string
   *   The title.
   */
  public function getTitle(?string $component_type = NULL, ?string $machine_name = NULL) {
    return $this->t('@component_name', ['@component_name' => ucfirst($machine_name ?? 'Component')]);
  }

  /**
   * Return a page with the iframe only.
   *
   * @param string|null $component_type
   *   The component type.
   * @param string|null $machine_name
   *   The component machine name.
   * @param string|null $provider
   *   The component provider.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   404 error when module is not found.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response with the twig render.
   */
  public function embed(?string $component_type = NULL, ?string $machine_name = NULL, ?string $provider = NULL): Response {
    $queryParameters = $this->request->getCurrentRequest()->query->all();
    $version = $queryParameters['version'] ?? NULL;
    $this->getComponentData($machine_name, $provider);

    if (
      is_null($machine_name)
      || is_null($component_type)
      || !in_array($provider, [NULL, ...array_keys($this->components)])
    ) {
      throw new NotFoundHttpException();
    }

    $pluginDefinition = $this->component->getPluginDefinition();
    $library = $pluginDefinition['library'] ?? NULL;
    $cssFiles = array_key_exists('css', $library) ? array_keys($library['css']['component']) : [];
    $jsFiles = array_key_exists('js', $library) ? array_keys($library['js']) : [];
    $flattenedLibraries = $this->componentFile->getLibrariesFilesFromExtension($provider);
    $cssFiles = array_merge($cssFiles, ($flattenedLibraries['css'] ?? []));
    $jsFiles = array_merge($jsFiles, ($flattenedLibraries['js'] ?? []));

    $variables = [
      'page_title' => $component_type . ' / ' . $this->getTitle(NULL, $machine_name),
      'stylesheets' => $cssFiles,
      'scripts' => $jsFiles,
      'data' => (!is_null($version) && array_key_exists($version, $this->versions)) ? $this->versions[$version] : reset($this->versions),
      'template_path' => $this->component->getPluginId(),
    ];

    $twigTemplatePath = $this->componentFile->getDabModulePath() . '/templates/embed.html.twig';
    $twigTemplate = file_get_contents($twigTemplatePath);
    $markup = $this->twig->renderInline($twigTemplate, $variables);
    $response = new Response($markup);
    $response->headers->set('Content-Type', 'text/html');

    return $response;
  }

  /**
   * Controller method to build the page with the component's iframe.
   *
   * @param string|null $component_type
   *   The component type.
   * @param string|null $machine_name
   *   The component machine name.
   * @param string|null $provider
   *   The component provider.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   404 error when module is not found.
   *
   * @return array
   *   The render array.
   */
  public function build(?string $component_type = NULL, ?string $machine_name = NULL, ?string $provider = NULL) {
    $queryParameters = $this->request->getCurrentRequest()->query->all();
    $responsive = $queryParameters['iframe-width'] ?? NULL;
    $version = $queryParameters['version'] ?? NULL;
    $this->getComponentData($machine_name, $provider);

    if (
         is_null($machine_name)
      || is_null($component_type)
      || !in_array($provider, [NULL, ...array_keys($this->components)])
    ) {
      throw new NotFoundHttpException();
    }

    $reloadButton = $this->formBuilder->getForm(CacheClearForm::class);
    $url = Url::fromRoute('dab.component_embed', [
      'component_type' => $component_type,
      'machine_name' => $machine_name,
      'provider' => $provider,
    ], ['query' => $queryParameters]);

    $resetUrl = Url::fromRoute('dab.component', [
      'component_type' => $component_type,
      'machine_name' => $machine_name,
      'provider' => $provider,
    ]);

    $pluginDefinition = $this->component->getPluginDefinition();
    $templateSelect = $this->getTemplateSelect($provider);
    $templateSelect['#value'] = $provider;

    return [
      '#theme' => 'dab_renderer',
      '#component_path' => $pluginDefinition['path'],
      '#iframe_src' => $url,
      '#reset_button' => [
        '#type' => 'link',
        '#title' => $this->t('Reset'),
        '#url' => $resetUrl,
        '#button_type' => 'primary',
        '#id' => 'reset-query-button',
        '#attributes' => [
          'class' => ['button'],
          'title' => $this->t('Reset'),
        ],
      ],
      '#reload_button' => $reloadButton,
      '#responsive_select' => $this->getResponsiveSelect($responsive),
      '#template_select' => $templateSelect,
      '#version_select' => $this->getVersionSelect($version),
      '#cache' => [
        'max-age' => 0,
        'tags' => ['url'],
      ],
      '#attached' => [
        'library' => [
          'dab/global',
        ],
        'drupalSettings' => [
          'dab_component' => [
            'base_path' => '/admin/dab/components',
            'route_parameters' => [
              'component_type' => $component_type,
              'machine_name' => $machine_name,
              'provider' => $provider,
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Controller method to build the documentation page.
   *
   * @param string|null $component_type
   *   The component type.
   * @param string|null $machine_name
   *   The component machine name.
   *
   * @return array
   *   The render array.
   */
  public function documentation(?string $component_type = NULL, ?string $machine_name = NULL): array {
    $this->getComponentData($machine_name);
    $pluginDefinition = $this->component->getPluginDefinition();
    $html = $this->markdown->convertToHtml($pluginDefinition['documentation']);

    return [
      '#type' => 'markup',
      '#title' => $this->t('Documentation'),
      '#markup' => $html,
    ];
  }

  /**
   * Get the responsive select to pass to the front.
   *
   * @param string|null $responsive
   *   The responsive value.
   *
   * @return array
   *   The render array of the select.
   */
  private function getResponsiveSelect(?string $responsive = NULL): array {
    return [
      '#type' => 'select',
      '#title' => $this->t('Responsive'),
      '#options' => [
        'reset' => $this->t('Base'),
        'desktop' => $this->t('Desktop'),
        'mobile' => $this->t('Mobile'),
        'tablet' => $this->t('Tablet'),
      ],
      '#value' => $responsive ?? 'reset',
      '#id' => 'iframe-resize-select',
    ];
  }

}
