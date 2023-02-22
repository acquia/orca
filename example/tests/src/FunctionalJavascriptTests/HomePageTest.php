<?php

namespace Drupal\Tests\example\FunctionalJavascriptTests;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

class HomePageTest extends WebDriverTestBase {

  protected $defaultTheme = 'claro';

  public function testHomePageVisit(): void {
    $page = $this->getSession()->getPage();
    $content = $page->findLink('Home');
    $this->assertTrue($content->isVisible());

  }

}
