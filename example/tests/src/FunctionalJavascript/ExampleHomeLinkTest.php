<?php

namespace Drupal\Tests\example\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

class ExampleHomeLinkTest extends WebDriverTestBase {

  protected $defaultTheme = 'claro';

  public function testHomePageLink(): void {
    $page = $this->getSession()->getPage();
    $content = $page->findLink('Log in');
    $this->assertTrue($content->isVisible());
  }

}
