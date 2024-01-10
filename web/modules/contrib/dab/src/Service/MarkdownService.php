<?php

namespace Drupal\dab\Service;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * The markdown service using league/commonmark library.
 */
final class MarkdownService {

  /**
   * The converter init.
   *
   * @return \League\CommonMark\MarkdownConverter
   *   The markdown converter.
   */
  private function initMarkdownConverter() {
    $environment = new Environment([]);
    $environment->addExtension(new CommonMarkCoreExtension());
    $environment->addExtension(new TableExtension());

    return new MarkdownConverter($environment);
  }

  /**
   * Convert markdown to html.
   *
   * @param string $markdown
   *   The markdown to convert.
   *
   * @return string
   *   The converted HTML.
   */
  public function convertToHtml(string $markdown) {
    $converter = $this->initMarkdownConverter();
    return $converter->convert($markdown);
  }

}
