<?php

namespace Acquia\Orca\Tests\Drush;

use Acquia\Orca\Drush\DrushFacade;
use Acquia\Orca\Exception\ParseError;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @property \Acquia\Orca\Utility\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Drush\DrushFacade
 */
class DrushFacadeTest extends TestCase {

  private $status = [
    'drupal-version' => '9.0.2',
    'uri' => 'http://default',
    'db-driver' => 'sqlite',
    'db-hostname' => 'localhost',
    'db-port' => '3306',
    'db-username' => 'drupal',
    'db-name' => '/var/www/orca-build/db.sqlite',
    'db-status' => 'Connected',
    'bootstrap' => 'Successful',
    'theme' => 'stark',
    'admin-theme' => 'seven',
    'php-bin' => '/usr/bin/php',
    'php-conf' => [
      '/usr/local/etc/php/7.3/php.ini' => '/usr/local/etc/php/7.3/php.ini',
    ],
    'php-os' => 'Darwin',
    'drush-script' => '/var/www/orca-build/vendor/drush/drush/drush',
    'drush-version' => '10.3.1',
    'drush-temp' => '/tmp',
    'drush-conf' => [
      0 => '/var/www/orca-build/vendor/drush/drush/drush.yml',
    ],
    'install-profile' => 'minimal',
    'root' => '/var/www/orca-build/docroot',
    'site' => 'sites/default',
    'files' => '/var/www/orca-build/docroot/sites/default/files',
    'private' => '/var/www/orca-build/files-private/default',
    'temp' => '/tmp',
  ];

  protected function setUp(): void {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
  }

  private function createDrushFacade(): DrushFacade {
    $process_runner = $this->processRunner->reveal();
    return new DrushFacade($process_runner);
  }

  /**
   * @dataProvider providerEnableExtensions
   */
  public function testEnableModules(array $modules, string $argument): void {
    $this->processRunner
      ->runFixtureVendorBin([
        'drush',
        'pm:enable',
        '--yes',
        $argument,
      ])
      ->shouldBeCalledOnce();

    $facade = $this->createDrushFacade();
    $facade->enableModules($modules);
  }

  /**
   * @dataProvider providerEnableExtensions
   */
  public function testEnableThemes(array $themes, string $argument): void {
    $this->processRunner
      ->runFixtureVendorBin([
        'drush',
        'theme:enable',
        $argument,
      ])
      ->shouldBeCalledOnce();

    $facade = $this->createDrushFacade();
    $facade->enableThemes($themes);
  }

  public function providerEnableExtensions() {
    return [
      [['test', 'example'], 'test,example'],
      [['example', 'test'], 'example,test'],
    ];
  }

  public function testGetDrushStatus(): void {
    $process = $this->prophesize(Process::class);
    $process
      ->run()
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $process
      ->getOutput()
      ->shouldBeCalledOnce()
      ->willReturn(json_encode($this->status));
    $this->processRunner
      ->createFixtureVendorBinProcess([
        'drush',
        'core:status',
        '--format=json',
      ])
      ->willReturn($process->reveal())
      ->shouldBeCalledOnce();

    $facade = $this->createDrushFacade();
    $status = $facade->getDrushStatus();

    self::assertSame($this->status, $status, 'Returned correct status.');
  }

  public function testGetDrushStatusWithInvalidJson(): void {
    $process = $this->prophesize(Process::class);
    $process
      ->run()
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $process
      ->getOutput()
      ->shouldBeCalledOnce()
      ->willReturn('');
    $this->processRunner
      ->createFixtureVendorBinProcess([
        'drush',
        'core:status',
        '--format=json',
      ])
      ->willReturn($process->reveal())
      ->shouldBeCalledOnce();
    $this->expectException(ParseError::class);

    $facade = $this->createDrushFacade();
    $status = $facade->getDrushStatus();

    self::assertSame($this->status, $status, 'Returned correct status.');
  }

  /**
   * @dataProvider providerInstallDrupal
   */
  public function testInstallDrupal(string $profile): void {
    $this->processRunner
      ->runFixtureVendorBin([
        'drush',
        'site:install',
        $profile,
        "install_configure_form.update_status_module='[FALSE,FALSE]'",
        'install_configure_form.enable_update_status_module=NULL',
        '--site-name=ORCA',
        '--account-name=admin',
        '--account-pass=admin',
        '--no-interaction',
        '--verbose',
        '--ansi',
      ])
      ->shouldBeCalledOnce();

    $facade = $this->createDrushFacade();
    $facade->installDrupal($profile);
  }

  public function providerInstallDrupal(): array {
    return [
      ['test'],
      ['example'],
    ];
  }

  public function testSetNodeFormsUseAdminTheme(): void {
    $this->processRunner
      ->runFixtureVendorBin([
        'drush',
        'config:set',
        'node.settings',
        'use_admin_theme',
        TRUE,
      ])
      ->shouldBeCalledOnce();

    $facade = $this->createDrushFacade();
    $facade->setNodeFormsUseAdminTheme();
  }

  public function testSetNodeFormsUseAdminThemeWithoutNodeModule(): void {
    $this->processRunner
      ->runFixtureVendorBin(Argument::any())
      ->shouldBeCalledOnce()
      ->willThrow(ProcessFailedException::class);

    $facade = $this->createDrushFacade();
    $facade->setNodeFormsUseAdminTheme();

    self::assertTrue(TRUE, 'Drush error was suppressed.');
  }

}
