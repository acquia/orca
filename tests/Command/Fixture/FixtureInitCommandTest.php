<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\FixtureInitCommand;
use Acquia\Orca\Enum\DrupalCoreVersion;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\FixtureCreator;
use Acquia\Orca\Fixture\FixtureRemover;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Fixture\SutPreconditionsTester;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Acquia\Orca\Utility\DrupalCoreVersionFinder;
use Composer\Semver\VersionParser;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\DrupalCoreVersionFinder $drupalCoreVersionFinder
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureCreator $fixtureCreator
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureRemover $fixtureRemover
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\SutPreconditionsTester sutPreconditionsTester
 * @property \Prophecy\Prophecy\ObjectProphecy|\Composer\Semver\VersionParser $versionParser
 */
class FixtureInitCommandTest extends CommandTestBase {

  private const CORE_VALUE_LITERAL_PREVIOUS_RELEASE = '8.5.14.0';

  private const CORE_VALUE_LITERAL_PREVIOUS_DEV = '8.5.x-dev';

  private const CORE_VALUE_LITERAL_CURRENT_RECOMMENDED = '8.6.14.0';

  private const CORE_VALUE_LITERAL_CURRENT_DEV = '8.6.x-dev';

  private const CORE_VALUE_LITERAL_NEXT_RELEASE = '8.7.0.0-beta2';

  private const CORE_VALUE_LITERAL_NEXT_DEV = '8.7.x-dev';

  protected function setUp() {
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->fixtureCreator = $this->prophesize(FixtureCreator::class);
    $this->fixtureRemover = $this->prophesize(FixtureRemover::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(FALSE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists(Argument::any())
      ->wilLReturn(TRUE);
    $this->sutPreconditionsTester = $this->prophesize(SutPreconditionsTester::class);
    $this->versionParser = $this->prophesize(VersionParser::class);
  }

  protected function createCommand(): Command {
    /** @var \Acquia\Orca\Utility\DrupalCoreVersionFinder $drupal_core_version_finder */
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    /** @var \Acquia\Orca\Fixture\FixtureCreator $fixture_creator */
    $fixture_creator = $this->fixtureCreator->reveal();
    /** @var \Acquia\Orca\Fixture\FixtureRemover $fixture_remover */
    $fixture_remover = $this->fixtureRemover->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->packageManager->reveal();
    /** @var \Acquia\Orca\Fixture\SutPreconditionsTester $sut_preconditions_tester */
    $sut_preconditions_tester = $this->sutPreconditionsTester->reveal();
    /** @var \Composer\Semver\VersionParser $version_parser */
    $version_parser = ($this->versionParser instanceof VersionParser) ? $this->versionParser : $this->versionParser->reveal();
    return new FixtureInitCommand($drupal_core_version_finder, $fixture, $fixture_creator, $fixture_remover, $package_manager, $sut_preconditions_tester, $version_parser);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $methods_called, $drupal_core_version, $status_code, $display) {
    $this->packageManager
      ->exists(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('PackageManager::exists', $methods_called))
      ->willReturn(@$args['--sut'] === self::VALID_PACKAGE);
    $this->fixture
      ->exists()
      ->shouldBeCalledTimes((int) in_array('Fixture::exists', $methods_called))
      ->willReturn($fixture_exists);
    $this->fixtureRemover
      ->remove()
      ->shouldBeCalledTimes((int) in_array('remove', $methods_called));
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion(DrupalCoreVersion::PREVIOUS_DEV))
      ->shouldBeCalledTimes((int) in_array('getPreviousMinorVersion', $methods_called))
      ->willReturn($drupal_core_version);
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion(DrupalCoreVersion::CURRENT_RECOMMENDED))
      ->shouldBeCalledTimes((int) in_array('getCurrentRecommendedVersion', $methods_called))
      ->willReturn(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED);
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion(DrupalCoreVersion::CURRENT_DEV))
      ->shouldBeCalledTimes((int) in_array('getCurrentDevVersion', $methods_called))
      ->willReturn($drupal_core_version);
    $this->fixtureCreator
      ->setSut(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('setSut', $methods_called));
    $this->fixtureCreator
      ->setSutOnly(TRUE)
      ->shouldBeCalledTimes((int) in_array('setSutOnly', $methods_called));
    $this->fixtureCreator
      ->setDev(TRUE)
      ->shouldBeCalledTimes((int) in_array('setDev', $methods_called));
    $this->fixtureCreator
      ->setCoreVersion($drupal_core_version ?: self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED)
      ->shouldBeCalledTimes((int) in_array('setCoreVersion', $methods_called));
    $this->fixtureCreator
      ->setSqlite(FALSE)
      ->shouldBeCalledTimes((int) in_array('setSqlite', $methods_called));
    $this->fixtureCreator
      ->setProfile((@$args['--profile']) ?: 'minimal')
      ->shouldBeCalledTimes((int) in_array('setProfile', $methods_called));
    $this->fixtureCreator
      ->setInstallSite(FALSE)
      ->shouldBeCalledTimes((int) in_array('setInstallSite', $methods_called));
    $this->fixtureCreator
      ->create()
      ->shouldBeCalledTimes((int) in_array('create', $methods_called));

    $this->executeCommand($args);

    $this->assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [TRUE, [], ['Fixture::exists', 'getCurrentRecommendedVersion', 'setCoreVersion'], NULL, StatusCode::ERROR, sprintf("Error: Fixture already exists at %s.\nHint: Use the \"--force\" option to remove it and proceed.\n", self::FIXTURE_ROOT)],
      [TRUE, ['-f' => TRUE], ['Fixture::exists', 'remove', 'create', 'getCurrentRecommendedVersion', 'setCoreVersion'], NULL, StatusCode::OK, ''],
      [FALSE, [], ['Fixture::exists', 'create', 'getCurrentRecommendedVersion', 'setCoreVersion'], NULL, StatusCode::OK, ''],
      [FALSE, ['--sut' => self::INVALID_PACKAGE], ['PackageManager::exists'], NULL, StatusCode::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [FALSE, ['--sut' => self::VALID_PACKAGE], ['PackageManager::exists', 'Fixture::exists', 'getCurrentRecommendedVersion', 'setCoreVersion', 'setSut', 'create'], NULL, StatusCode::OK, ''],
      [FALSE, ['--sut' => self::VALID_PACKAGE, '--sut-only' => TRUE], ['PackageManager::exists', 'Fixture::exists', 'getCurrentRecommendedVersion', 'setCoreVersion', 'setSut', 'setSutOnly', 'create'], NULL, StatusCode::OK, ''],
      [FALSE, ['--dev' => TRUE], ['Fixture::exists', 'setDev', 'getCurrentRecommendedVersion', 'setCoreVersion', 'create'], self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED, StatusCode::OK, ''],
      [FALSE, ['--no-site-install' => TRUE], ['Fixture::exists', 'getCurrentRecommendedVersion', 'setCoreVersion', 'setInstallSite', 'create'], NULL, StatusCode::OK, ''],
      [FALSE, ['--no-sqlite' => TRUE], ['Fixture::exists', 'getCurrentRecommendedVersion', 'setCoreVersion', 'setSqlite', 'create'], NULL, StatusCode::OK, ''],
      [FALSE, ['--profile' => 'lightning'], ['Fixture::exists', 'getCurrentRecommendedVersion', 'setCoreVersion', 'setProfile', 'create'], NULL, StatusCode::OK, ''],
      [FALSE, ['--sut-only' => TRUE], [], NULL, StatusCode::ERROR, "Error: Cannot create a SUT-only fixture without a SUT.\nHint: Use the \"--sut\" option to specify the SUT.\n"],
    ];
  }

  public function testNoOptions() {
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion(DrupalCoreVersion::CURRENT_RECOMMENDED))
      ->shouldBeCalledOnce()
      ->willReturn(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED);
    $this->versionParser = new VersionParser();

    $this->executeCommand();

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testBareOption() {
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion(DrupalCoreVersion::CURRENT_RECOMMENDED))
      ->shouldBeCalledOnce()
      ->willReturn(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED);
    $this->fixtureCreator
      ->setBare(TRUE)
      ->shouldBeCalledTimes(1);
    $this->fixtureCreator
      ->setCoreVersion(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED)
      ->shouldBeCalledTimes(1);
    $this->fixtureCreator
      ->create()
      ->shouldBeCalledTimes(1);

    $this->executeCommand(['--bare' => TRUE]);

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  /**
   * @dataProvider providerBareOptionInvalidProvider
   */
  public function testBareOptionInvalid($options) {
    $this->fixtureCreator
      ->create()
      ->shouldNotBeCalled();

    $this->executeCommand($options);

    $this->assertEquals("Error: Cannot create a bare fixture with a SUT.\n", $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerBareOptionInvalidProvider() {
    return [
      [['--bare' => TRUE, '--sut' => 'drupal/example']],
      [['--bare' => TRUE, '--sut' => 'drupal/example', '--sut-only' => TRUE]],
    ];
  }

  /**
   * @dataProvider providerCoreOption
   */
  public function testCoreOption($value, $finder_calls, $options, $set_version) {
    $this->drupalCoreVersionFinder
      ->get($value)
      ->shouldBeCalledTimes($finder_calls)
      ->willReturn($set_version);
    $this->fixtureCreator
      ->setDev(TRUE)
      ->shouldBeCalledTimes((int) isset($options['--dev']));
    $this->fixtureCreator
      ->setCoreVersion($set_version)
      ->shouldBeCalledTimes(1);
    $this->fixtureCreator
      ->create()
      ->shouldBeCalledTimes(1);

    $this->executeCommand($options);

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCoreOption() {
    return [
      [new DrupalCoreVersion(DrupalCoreVersion::PREVIOUS_RELEASE), 1, ['--core' => DrupalCoreVersion::PREVIOUS_RELEASE], self::CORE_VALUE_LITERAL_PREVIOUS_RELEASE],
      [new DrupalCoreVersion(DrupalCoreVersion::PREVIOUS_DEV), 1, ['--core' => DrupalCoreVersion::PREVIOUS_DEV], self::CORE_VALUE_LITERAL_PREVIOUS_DEV],
      [new DrupalCoreVersion(DrupalCoreVersion::CURRENT_RECOMMENDED), 1, ['--core' => DrupalCoreVersion::CURRENT_RECOMMENDED], self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED],
      [new DrupalCoreVersion(DrupalCoreVersion::CURRENT_DEV), 1, ['--core' => DrupalCoreVersion::CURRENT_DEV], self::CORE_VALUE_LITERAL_CURRENT_DEV],
      [new DrupalCoreVersion(DrupalCoreVersion::NEXT_RELEASE), 1, ['--core' => DrupalCoreVersion::NEXT_RELEASE], self::CORE_VALUE_LITERAL_NEXT_RELEASE],
      [new DrupalCoreVersion(DrupalCoreVersion::NEXT_DEV), 1, ['--core' => DrupalCoreVersion::NEXT_DEV], self::CORE_VALUE_LITERAL_NEXT_DEV],
      [self::CORE_VALUE_LITERAL_PREVIOUS_RELEASE, 0, ['--core' => self::CORE_VALUE_LITERAL_PREVIOUS_RELEASE], self::CORE_VALUE_LITERAL_PREVIOUS_RELEASE],
      [self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED, 0, ['--core' => self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED], self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED],
      [self::CORE_VALUE_LITERAL_CURRENT_DEV, 0, ['--core' => self::CORE_VALUE_LITERAL_CURRENT_DEV], self::CORE_VALUE_LITERAL_CURRENT_DEV],
      [self::CORE_VALUE_LITERAL_NEXT_RELEASE, 0, ['--core' => self::CORE_VALUE_LITERAL_NEXT_RELEASE], self::CORE_VALUE_LITERAL_NEXT_RELEASE],
    ];
  }

  /**
   * @dataProvider providerCoreOptionVersionParsing
   */
  public function testCoreOptionVersionParsing($status_code, $value, $display) {
    $this->versionParser = new VersionParser();

    $this->executeCommand([
      '--core' => $value,
    ]);

    $this->assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
    $this->assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
  }

  public function providerCoreOptionVersionParsing() {
    $error_message = 'Error: Invalid value for "--core" option: "%s".' . PHP_EOL
      . 'Hint: Acceptable values are "PREVIOUS_RELEASE", "PREVIOUS_DEV", "CURRENT_RECOMMENDED", "CURRENT_DEV", "NEXT_RELEASE", "NEXT_DEV", "D9_READINESS", or any version string Composer understands.' . PHP_EOL;
    return [
      [StatusCode::OK, self::CORE_VALUE_LITERAL_PREVIOUS_RELEASE, ''],
      [StatusCode::OK, self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED, ''],
      [StatusCode::OK, self::CORE_VALUE_LITERAL_CURRENT_DEV, ''],
      [StatusCode::OK, self::CORE_VALUE_LITERAL_NEXT_RELEASE, ''],
      [StatusCode::OK, '^1.0', ''],
      [StatusCode::OK, '~1.0', ''],
      [StatusCode::OK, '>=1.0', ''],
      [StatusCode::OK, 'dev-topic-branch', ''],
      [StatusCode::ERROR, 'garbage', sprintf($error_message, 'garbage')],
      [StatusCode::ERROR, '1.0.x-garbage', sprintf($error_message, '1.0.x-garbage')],
    ];
  }

  /**
   * @dataProvider providerIgnorePatchFailureOption
   */
  public function testIgnorePatchFailureOption($options, $num_calls) {
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion(DrupalCoreVersion::CURRENT_RECOMMENDED))
      ->shouldBeCalledOnce()
      ->willReturn(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED);
    $this->fixtureCreator
      ->setComposerExitOnPatchFailure(FALSE)
      ->shouldBeCalledTimes($num_calls);
    $this->fixtureCreator
      ->setCoreVersion(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED)
      ->shouldBeCalledTimes(1);
    $this->fixtureCreator
      ->create()
      ->shouldBeCalledTimes(1);

    $this->executeCommand($options);

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerIgnorePatchFailureOption() {
    return [
      [['--ignore-patch-failure' => TRUE], 1],
      [[], 0],
    ];
  }

  public function testFixtureCreationFailure() {
    $exception_message = 'Failed to create fixture.';
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion(DrupalCoreVersion::CURRENT_RECOMMENDED))
      ->shouldBeCalledOnce()
      ->willReturn(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED);
    $this->fixtureCreator
      ->setCoreVersion(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED)
      ->shouldBeCalledTimes(1);
    $this->fixtureCreator
      ->create(Argument::any())
      ->willThrow(new OrcaException($exception_message));

    $this->executeCommand();

    $this->assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
    $this->assertContains("[ERROR] {$exception_message}", $this->getDisplay(), 'Displayed correct output.');
  }

  public function testPreferSourceOption() {
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion(DrupalCoreVersion::CURRENT_RECOMMENDED))
      ->shouldBeCalledOnce()
      ->willReturn(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED);
    $this->fixtureCreator
      ->setCoreVersion(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED)
      ->shouldBeCalledTimes(1);
    $this->fixtureCreator
      ->setPreferSource(TRUE)
      ->shouldBeCalledTimes(1);
    $this->fixtureCreator
      ->create()
      ->shouldBeCalledTimes(1);

    $this->executeCommand(['--prefer-source' => TRUE]);

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testSutPreconditionTestFailure() {
    $sut_name = 'drupal/example';
    $exception_message = 'Failed to create fixture.';

    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion(DrupalCoreVersion::CURRENT_RECOMMENDED))
      ->shouldBeCalledOnce()
      ->willReturn(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixtureRemover
      ->remove()
      ->shouldNotBeCalled();
    $this->sutPreconditionsTester
      ->test($sut_name)
      ->willThrow(new OrcaException($exception_message));

    $this->executeCommand([
      '--force' => TRUE,
      '--sut' => $sut_name,
    ]);

    $this->assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
    $this->assertContains("[ERROR] {$exception_message}", $this->getDisplay(), 'Displayed correct output.');
  }

  /**
   * @dataProvider providerSymlinkAllOption
   */
  public function testSymlinkAllOption($options, $num_calls) {
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion(DrupalCoreVersion::CURRENT_RECOMMENDED))
      ->shouldBeCalledOnce()
      ->willReturn(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED);
    $this->fixtureCreator
      ->setSymlinkAll(TRUE)
      ->shouldBeCalledTimes($num_calls);
    $this->fixtureCreator
      ->setCoreVersion(self::CORE_VALUE_LITERAL_CURRENT_RECOMMENDED)
      ->shouldBeCalledTimes(1);
    $this->fixtureCreator
      ->create()
      ->shouldBeCalledTimes(1);

    $this->executeCommand($options);

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerSymlinkAllOption() {
    return [
      [['--symlink-all' => TRUE], 1],
      [[], 0],
    ];
  }

  /**
   * @dataProvider providerSymlinkAllOptionInvalid
   */
  public function testSymlinkAllOptionInvalid($options) {
    $this->fixtureCreator
      ->create()
      ->shouldNotBeCalled();

    $this->executeCommand($options);

    $this->assertEquals("Error: Cannot symlink all in a bare fixture.\n", $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerSymlinkAllOptionInvalid() {
    return [
      [['--bare' => TRUE, '--symlink-all' => TRUE]],
    ];
  }

}
