<?php

namespace Acquia\Orca\Tests\Domain\Tool\Coverage;

use Acquia\Orca\Domain\Tool\Coverage\CodeCoverageReportBuilder;
use Acquia\Orca\Domain\Tool\Phploc\PhplocTask;
use Acquia\Orca\Exception\OrcaDirectoryNotFoundException;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FinderFactory;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Tests\TestCase;
use Noodlehaus\Config;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundException;
use Noodlehaus\Exception\ParseException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException as FinderDirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @property \Acquia\Orca\Helper\Config\ConfigLoader|\Prophecy\Prophecy\ObjectProphecy $configLoader
 * @property \Acquia\Orca\Helper\Filesystem\FinderFactory|\Prophecy\Prophecy\ObjectProphecy $finderFactory
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \ArrayIterator $configIterator
 * @property \ArrayIterator $extensionIterator
 * @property \ArrayIterator $phpIterator
 * @property \ArrayIterator $testIterator
 * @property \Noodlehaus\Config|\Prophecy\Prophecy\ObjectProphecy $config
 * @property \Symfony\Component\Finder\Finder|\Prophecy\Prophecy\ObjectProphecy $configFinder
 * @property \Symfony\Component\Finder\Finder|\Prophecy\Prophecy\ObjectProphecy $extensionFinder
 * @property \Symfony\Component\Finder\Finder|\Prophecy\Prophecy\ObjectProphecy $phpFinder
 * @property \Symfony\Component\Finder\Finder|\Prophecy\Prophecy\ObjectProphecy $testFinder
 * @coversDefaultClass \Acquia\Orca\Domain\Tool\Coverage\CodeCoverageReportBuilder
 */
class CodeCoverageReportBuilderTest extends TestCase {

  private const DEFAULT_PATH = 'test/example';
  protected Finder|ObjectProphecy $configFinder;
  protected Finder|ObjectProphecy $extensionFinder;
  protected Finder|ObjectProphecy $phpFinder;
  protected Finder|ObjectProphecy $testFinder;
  protected ConfigLoader|ObjectProphecy $configLoader;
  protected FinderFactory|ObjectProphecy $finderFactory;
  protected OrcaPathHandler|ObjectProphecy $orca;
  protected Config|ObjectProphecy $config;
  protected \ArrayIterator $configIterator;
  protected \ArrayIterator $extensionIterator;
  protected \ArrayIterator $phpIterator;
  protected \ArrayIterator $testIterator;


  private $phplocData = [
    'directories' => 23,
    'files' => 78,
    'loc' => 9593,
    'lloc' => 2011,
    'llocClasses' => 1550,
    'llocFunctions' => 2,
    'llocGlobal' => 459,
    'cloc' => 4065,
    'ccn' => 374,
    'ccnMethods' => 369,
    'interfaces' => 3,
    'traits' => 1,
    'classes' => 71,
    'abstractClasses' => 4,
    'concreteClasses' => 67,
    'functions' => 5,
    'namedFunctions' => 2,
    'anonymousFunctions' => 3,
    'methods' => 393,
    'publicMethods' => 204,
    'nonPublicMethods' => 189,
    'nonStaticMethods' => 391,
    'staticMethods' => 2,
    'constants' => 27,
    'classConstants' => 27,
    'globalConstants' => 0,
    'testClasses' => 0,
    'testMethods' => 0,
    'ccnByLloc' => 0.1859771258080557,
    'llocByNof' => 0.4,
    'methodCalls' => 1101,
    'staticMethodCalls' => 42,
    'instanceMethodCalls' => 1059,
    'attributeAccesses' => 803,
    'staticAttributeAccesses' => 16,
    'instanceAttributeAccesses' => 787,
    'globalAccesses' => 2,
    'globalVariableAccesses' => 0,
    'superGlobalVariableAccesses' => 2,
    'globalConstantAccesses' => 0,
    'classCcnMin' => 1,
    'classCcnAvg' => 5.92,
    'classCcnMax' => 60,
    'classLlocMin' => 0,
    'classLlocAvg' => 20.666666666666668,
    'classLlocMax' => 257,
    'methodCcnMin' => 1,
    'methodCcnAvg' => 1.9605263157894737,
    'methodCcnMax' => 18,
    'methodLlocMin' => 1,
    'methodLlocAvg' => 3.3263157894736843,
    'methodLlocMax' => 26,
    'namespaces' => 21,
    'ncloc' => 5528,
  ];

  protected function setUp(): void {
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->config = $this->prophesize(Config::class);

    $this->finderFactory = $this->prophesize(FinderFactory::class);

    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->orca
      ->getPath(PhplocTask::JSON_LOG_PATH)
      ->willReturnArgument();

    $this->phpFinder = $this->prophesize(Finder::class);
    $this->phpFinder
      ->in(Argument::any())
      ->willReturn($this->phpFinder);
    $this->phpFinder
      ->name(Argument::any())
      ->willReturn($this->phpFinder);
    $this->phpFinder
      ->notPath(Argument::any())
      ->willReturn($this->phpFinder);
    $php_file = $this->prophesize(SplFileInfo::class);
    $this->phpIterator = new \ArrayIterator([$php_file->reveal()]);

    $this->testFinder = $this->prophesize(Finder::class);
    $this->testFinder
      ->in(Argument::any())
      ->willReturn($this->testFinder);
    $this->testFinder
      ->name(Argument::any())
      ->willReturn($this->testFinder);
    $this->testFinder
      ->notPath(Argument::any())
      ->willReturn($this->testFinder);
    $this->testFinder
      ->contains(Argument::any())
      ->willReturn($this->testFinder);
    $test_file = $this->prophesize(SplFileInfo::class);
    $this->testIterator = new \ArrayIterator([$test_file->reveal()]);

    $this->extensionFinder = $this->prophesize(Finder::class);
    $this->extensionFinder
      ->in(Argument::any())
      ->willReturn($this->extensionFinder);
    $this->extensionFinder
      ->name(Argument::any())
      ->willReturn($this->extensionFinder);
    $this->extensionFinder
      ->notPath(Argument::any())
      ->willReturn($this->extensionFinder);
    $this->extensionFinder
      ->files()
      ->willReturn($this->extensionFinder);
    $extension_info_file = $this->prophesize(SplFileInfo::class);
    $this->extensionIterator = new \ArrayIterator([$extension_info_file->reveal()]);

    $this->configFinder = $this->prophesize(Finder::class);
    $this->configFinder
      ->in(Argument::any())
      ->willReturn($this->configFinder);
    $this->configFinder
      ->name(Argument::any())
      ->willReturn($this->configFinder);
    $this->configFinder
      ->notPath(Argument::any())
      ->willReturn($this->configFinder);
    $this->configFinder
      ->files()
      ->willReturn($this->configFinder);
    $config_file = $this->prophesize(SplFileInfo::class);
    $this->configIterator = new \ArrayIterator([$config_file->reveal()]);
  }

  private function createBuilder(): CodeCoverageReportBuilder {
    $this->config
      ->all()
      ->willReturn($this->phplocData);
    $config = $this->config->reveal();
    $this->configLoader
      ->load(Argument::any())
      ->willReturn($config);
    $config_loader = $this->configLoader->reveal();
    $this->phpFinder
      ->getIterator()
      ->willReturn($this->phpIterator);
    $php_finder = $this->phpFinder->reveal();
    $this->testFinder
      ->getIterator()
      ->willReturn($this->testIterator);
    $test_finder = $this->testFinder->reveal();
    $this->extensionFinder
      ->getIterator()
      ->willReturn($this->extensionIterator);
    $extension_finder = $this->extensionFinder->reveal();
    $this->configFinder
      ->getIterator()
      ->willReturn($this->configIterator);
    $config_finder = $this->configFinder->reveal();
    $this->finderFactory
      ->create()
      // @todo This temporal coupling (i.e., dependence on the order of
      //   execution) suggests suboptimal design in the production code.
      ->willReturn(
        $php_finder,
        $test_finder,
        $extension_finder,
        $config_finder
      );
    $finder_factory = $this->finderFactory->reveal();
    $orca_path_handler = $this->orca->reveal();
    return new CodeCoverageReportBuilder($config_loader, $finder_factory, $orca_path_handler);
  }

  /**
   * @dataProvider providerHappyPath
   */
  public function testHappyPath($path, $numerator, $assertions, $denominator, $complexity, $config_file_count, $score): void {
    // @todo The following unwieldy test arrangement is mostly setting up
    //   FinderFactories and Finders. This complexity may point to a need to
    //   reconsider the corresponding production code design.
    $this->phplocData['ccn'] = $complexity;
    $this->testFinder
      ->in($path)
      ->shouldBeCalledOnce()
      ->willReturn($this->testFinder);
    $this->testFinder
      ->name('*Test.php')
      ->shouldBeCalledOnce()
      ->willReturn($this->testFinder);
    $test_file_info = $this->prophesize(SplFileInfo::class);
    $test_file_info
      ->getContents()
      ->willReturn('self::assertTrue(TRUE);');
    $test_file_info = $test_file_info->reveal();
    $test_files = array_fill(0, $assertions, $test_file_info);
    $this->testIterator = new \ArrayIterator($test_files);
    $this->extensionFinder
      ->in(Argument::any())
      ->shouldBeCalledOnce()
      ->willReturn($this->extensionFinder);
    $this->extensionFinder
      ->name('*.info.yml')
      ->shouldBeCalledOnce()
      ->willReturn($this->extensionFinder);
    $this->extensionFinder
      ->notPath(Argument::any())
      ->shouldBeCalledTimes(2)
      ->willReturn($this->extensionFinder);
    $this->extensionFinder
      ->notPath('tests')
      ->shouldBeCalledOnce()
      ->willReturn($this->extensionFinder);
    $this->extensionFinder
      ->files()
      ->shouldBeCalledOnce()
      ->willReturn($this->extensionFinder);
    $extensions_file_info = $this->prophesize(SplFileInfo::class);
    $extensions_file_info
      ->getPath()
      ->willReturn('/example/path');
    $extensions_file_info = $extensions_file_info->reveal();
    $extension_files = array_fill(0, 1, $extensions_file_info);
    $this->extensionIterator = new \ArrayIterator($extension_files);
    $this->configFinder
      ->in($path)
      ->willReturn($this->configFinder);
    $this->configFinder
      ->path('config')
      ->willReturn($this->configFinder);
    $this->configFinder
      ->name('*.yml')
      ->willReturn($this->configFinder);
    $config_file_info = $this->prophesize(SplFileInfo::class);
    $config_file_info = $config_file_info->reveal();
    $config_files = array_fill(0, $config_file_count, $config_file_info);
    $this->configIterator = new \ArrayIterator($config_files);
    $builder = $this->createBuilder();

    $report = $builder->build($path);

    self::assertEquals([
      ['Health score', $score],
      ['  Numerator', $numerator],
      ['    Test assertions', $assertions],
      ['  Denominator', $denominator],
      ['    Cyclomatic complexity', $complexity],
      ['    Exported config files', $config_file_count],
    ], $report, 'Returned correct report data.');
  }

  public static function providerHappyPath(): array {
    return [
      [
        'path' => 'test/example',
        'numerator' => 100,
        'assertions' => 100,
        'denominator' => 100,
        'complexity' => 100,
        'config_file_count' => 0,
        'score' => '1.00',
      ],
      [
        'path' => 'test/example',
        'numerator' => 200,
        'assertions' => 200,
        'denominator' => 100,
        'complexity' => 100,
        'config_file_count' => 0,
        'score' => '2.00',
      ],
      [
        'path' => 'test/example',
        'numerator' => 100,
        'assertions' => 100,
        'denominator' => 200,
        'complexity' => 200,
        'config_file_count' => 0,
        'score' => '0.50',
      ],
      [
        'path' => 'test/example',
        'numerator' => 100,
        'assertions' => 100,
        'denominator' => 1,
        'complexity' => 1,
        'config_file_count' => 0,
        'score' => '100.00',
      ],
      [
        'path' => 'test/example',
        'numerator' => 0,
        'assertions' => 0,
        'denominator' => 100,
        'complexity' => 100,
        'config_file_count' => 0,
        'score' => '0.00',
      ],
      [
        'path' => 'test/example',
        'numerator' => 100,
        'assertions' => 100,
        'denominator' => 0,
        'complexity' => 0,
        'config_file_count' => 0,
        'score' => '0.00',
      ],
      [
        'path' => 'test/example',
        'numerator' => 33,
        'assertions' => 33,
        'denominator' => 375,
        'complexity' => 375,
        'config_file_count' => 0,
        'score' => '0.09',
      ],
      [
        'path' => 'test/example',
        'numerator' => 161,
        'assertions' => 161,
        'denominator' => 387,
        'complexity' => 387,
        'config_file_count' => 0,
        'score' => '0.42',
      ],
      [
        'path' => 'lorem/ipsum',
        'numerator' => 1,
        'assertions' => 1,
        'denominator' => 1,
        'complexity' => 1,
        'config_file_count' => 0,
        'score' => '1.00',
      ],
      [
        'path' => 'lorem/ipsum',
        'numerator' => 10,
        'assertions' => 10,
        'denominator' => 2,
        'complexity' => 1,
        'config_file_count' => 1,
        'score' => '5.00',
      ],
      [
        'path' => 'lorem/ipsum',
        'numerator' => 10,
        'assertions' => 10,
        'denominator' => 100,
        'complexity' => 1,
        'config_file_count' => 99,
        'score' => '0.10',
      ],
      [
        'path' => 'lorem/ipsum',
        'numerator' => 20,
        'assertions' => 20,
        'denominator' => 20,
        'complexity' => 10,
        'config_file_count' => 10,
        'score' => '1.00',
      ],
    ];
  }

  public function testPathDoesNotExistOrIsNotDirectory(): void {
    $message = 'Example message';
    $this->testFinder
      ->in(self::DEFAULT_PATH)
      ->willThrow(new FinderDirectoryNotFoundException($message));
    $this->expectException(OrcaDirectoryNotFoundException::class);
    $this->expectExceptionMessage($message);

    $builder = $this->createBuilder();

    $builder->build(self::DEFAULT_PATH);
  }

  public function testNoFilesFoundToScan(): void {
    $this->phpIterator = new \ArrayIterator([]);
    $this->expectException(OrcaFileNotFoundException::class);
    $builder = $this->createBuilder();

    $builder->build(self::DEFAULT_PATH);
  }

  public function testNoCoverageData(): void {
    $this->orca
      ->getPath(PhplocTask::JSON_LOG_PATH)
      ->shouldBeCalledOnce();
    $this->configLoader
      ->load(PhplocTask::JSON_LOG_PATH)
      ->shouldBeCalledOnce()
      ->willThrow(NoodlehausFileNotFoundException::class);
    $this->expectException(OrcaFileNotFoundException::class);
    $builder = $this->createBuilder();

    $builder->build(self::DEFAULT_PATH);
  }

  public function testInvalidCoverageData(): void {
    $this->orca
      ->getPath(PhplocTask::JSON_LOG_PATH)
      ->shouldBeCalledOnce();
    $this->configLoader
      ->load(PhplocTask::JSON_LOG_PATH)
      ->shouldBeCalledOnce()
      ->willThrow(ParseException::class);
    $this->expectException(OrcaParseError::class);
    $builder = $this->createBuilder();

    $builder->build(self::DEFAULT_PATH);
  }

}
