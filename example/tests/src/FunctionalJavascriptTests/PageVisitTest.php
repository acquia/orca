<?php

namespace Drupal\Tests\example\FunctionalJavascriptTests;

// Use Drupal\FunctionalJavascriptTests\WebDriverTestBase;.
use Drupal\Tests\BrowserTestBase;

class PageVisitTest extends BrowserTestBase {

  protected $defaultTheme = 'claro';

  private $privilegedUser;

  public function testPageVisit(): void {
    $this->privilegedUser = $this->drupalCreateUser();
    $this->drupalLogin($this->privilegedUser);

    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);

  }

}
