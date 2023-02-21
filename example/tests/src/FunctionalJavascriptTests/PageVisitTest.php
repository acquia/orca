<?php

namespace Drupal\Tests\example\FunctionalJavascriptTests;

// Use Drupal\FunctionalJavascriptTests\WebDriverTestBase;.
use Drupal\Tests\BrowserTestBase;

class PageVisitTest extends BrowserTestBase {

  protected $defaultTheme = 'claro';

  private $privilegedUser;

  public function testPageVisit(): void {
    // parent::setUp();
    // $mink = new Mink();
    // $mink->getSession()->getPage()->findLink('/');
    // // $page = $mink->getSession()->getPage();
    // // $page->findLink('Home');
    // self::assertTrue(TRUE);
    // Create a privileged user.
    // $permissions = ['grant content access'];.
    $this->privilegedUser = $this->drupalCreateUser();
    $this->drupalLogin($this->privilegedUser);

    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);

  }

}
