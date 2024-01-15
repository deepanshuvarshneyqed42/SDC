<?php

namespace Drupal\Tests\sdc_block\Unit\Render;

use DG\BypassFinals;
use Drupal\Core\Utility\Token;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Plugin\Component;
use Drupal\sdc_block\Render\ComponentBlockRenderer;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the ComponentBlockRender.
 *
 * @group sdc_block
 *
 * @coversDefaultClass \Drupal\sdc_block\Render\ComponentBlockRenderer
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class ComponentBlockRenderTest extends UnitTestCase {

  /**
   * The system under test.
   *
   * @var \Drupal\sdc_block\Render\ComponentBlockRenderer
   */
  private ComponentBlockRenderer $theSut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    BypassFinals::enable();
    $this->theSut = new ComponentBlockRenderer(
      $this->prophesize(Token::class)->reveal(),
      $this->prophesize(ComponentPluginManager::class)->reveal(),
    );
  }

  /**
   * @covers ::build
   */
  public function testBuild() {
    $component = new Component(['app_root' => '/tmp'], 'sdc_test:my-cta', [
      'template' => $this->randomString(),
      'machineName' => $this->randomString(),
      'description' => $this->randomString(),
      'group' => $this->randomString(),
      'path' => $this->randomString(),
      'id' => $this->randomString() . ':' . $this->randomString(),
      'name' => 'CTA',
    ]);
    $build = $this->theSut->build(
      $component,
      ['foo' => 'bar'],
      ['lorem' => ['value' => 'ipsum', 'format' => 'dolor']],
      []
    );
    $partial_build = array_intersect_key($build, array_flip([
      '#type',
      '#component',
      '#slots',
      '#props',
    ]));
    $this->assertEquals([
      '#type' => 'component',
      '#component' => 'sdc_test:my-cta',
      '#props' => ['foo' => 'bar'],
      '#slots' => [
        'lorem' => [
          '#type' => 'processed_text',
          '#text' => 'ipsum',
          '#format' => 'dolor',
        ],
      ],
    ], $partial_build);
  }

}
