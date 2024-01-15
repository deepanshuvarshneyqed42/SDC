<?php

namespace Drupal\Tests\sdc_block\Unit\Plugin\Derivative;

use Drupal\cl_editorial\NoThemeComponentManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\sdc\Plugin\Component;
use Drupal\sdc_block\Plugin\Derivative\ComponentBlockDeriver;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the ComponentBlockDeriver.
 *
 * @group sdc_block
 *
 * @coversDefaultClass \Drupal\sdc_block\Plugin\Derivative\ComponentBlockDeriver
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class ComponentBlockDeriverTest extends UnitTestCase {

  /**
   * The system under test.
   *
   * @var \Drupal\sdc_block\Plugin\Derivative\ComponentBlockDeriver
   */
  private ComponentBlockDeriver $theSut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $manager = $this->prophesize(NoThemeComponentManager::class);
    $random_definition = [
      'template' => $this->randomString(),
      'machineName' => $this->randomString(),
      'description' => $this->randomString(),
      'group' => $this->randomString(),
      'path' => $this->randomString(),
      'id' => $this->randomString() . ':' . $this->randomString(),
    ];
    $components = [
      new Component(['app_root' => '/tmp'], 'sdc_test:my-cta', array_merge($random_definition, ['name' => 'CTA'])),
      new Component(['app_root' => '/tmp'], 'sdc_test:my-banner', array_merge($random_definition, ['name' => 'Banner'])),
    ];
    $filters = [
      'allowed' => ['sdc_test:my-banner'],
      'statuses' => ['stable'],
    ];
    $manager->getFilteredComponents($filters['allowed'], [], $filters['statuses'])->willReturn($components);
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config_factory->get('sdc_tags.settings')->willReturn($config->reveal());
    $config->get('component_tags.sdc_block:block')->willReturn($filters);
    $this->theSut = new ComponentBlockDeriver(
      $manager->reveal(),
      $config_factory->reveal(),
    );
  }

  /**
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitions() {
    $derivatives = $this->theSut->getDerivativeDefinitions(['category' => 'bar']);
    $this->assertEquals(
      [
        'sdc_test:my-cta' => [
          'category' => 'bar',
          'admin_label' => 'CTA',
          'component_id' => 'sdc_test:my-cta',
        ],
        'sdc_test:my-banner' => [
          'category' => 'bar',
          'admin_label' => 'Banner',
          'component_id' => 'sdc_test:my-banner',
        ],
      ],
      $derivatives
    );
  }

}
