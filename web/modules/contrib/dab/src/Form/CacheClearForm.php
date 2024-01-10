<?php

namespace Drupal\dab\Form;

use Drupal\Core\Asset\CssCollectionOptimizerLazy;
use Drupal\Core\Asset\JsCollectionOptimizerLazy;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\TwigEnvironment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a cache clearing form.
 */
final class CacheClearForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The js collection optimizer.
   *
   * @var \Drupal\Core\Asset\JsCollectionOptimizerLazy
   */
  protected $jsCollectionOptimizer;

  /**
   * The css collection optimizer.
   *
   * @var \Drupal\Core\Asset\CssCollectionOptimizerLazy
   */
  protected $cssCollectionOptimizer;

  /**
   * The twig service.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cache_clear_form';
  }

  /**
   * The construct.
   *
   * @param \Drupal\Core\Asset\JsCollectionOptimizerLazy $js_collection_optimizer
   *   The js collection optimizer.
   * @param \Drupal\Core\Asset\CssCollectionOptimizerLazy $css_collection_optimizer
   *   The css collection optimizer.
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   The twig service.
   */
  public function __construct(
    JsCollectionOptimizerLazy $js_collection_optimizer,
    CssCollectionOptimizerLazy $css_collection_optimizer,
    TwigEnvironment $twig
  ) {
    $this->jsCollectionOptimizer = $js_collection_optimizer;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->twig = $twig;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('asset.js.collection_optimizer'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('twig'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['cache_clear'] = [
      '#type' => 'submit',
      '#id' => 'cache-reload-button',
      '#value' => '↻',
      '#button_type' => 'primary',
      '#attributes' => [
        'title' => $this->t('Reload the page and clear all cache on click or keyboard shortcut'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Flush asset file caches.
    $this->jsCollectionOptimizer->deleteAll();
    $this->cssCollectionOptimizer->deleteAll();
    _drupal_flush_css_js();

    // Wipe the Twig PHP Storage cache.
    $this->twig->invalidate();
  }

}
