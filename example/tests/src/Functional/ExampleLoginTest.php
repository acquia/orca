<?php

namespace Drupal\Tests\example\Functional;

// Use Drupal\FunctionalJavascriptTests\WebDriverTestBase;.
use Drupal\Tests\BrowserTestBase;

class ExampleLoginTest extends BrowserTestBase {

  protected $defaultTheme = 'claro';

  private $privilegedUser;

  public function testExampleLogin(): void {
    $this->privilegedUser = $this->drupalCreateUser();
    $this->drupalLogin($this->privilegedUser);

    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);

  }

}
