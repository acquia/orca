<?php

namespace Acquia\Orca\Tests\Console\Command\Report;

use Acquia\Orca\Console\Command\Report\ReportCodeCoverageCommand;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Exception\DirectoryNotFoundException as OrcaDirectoryNotFoundException;
use Acquia\Orca\Exception\FileNotFoundException;
use Acquia\Orca\Exception\ParseError;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Report\CodeCoverageReportBuilder;
use Acquia\Orca\Task\StaticAnalysisTool\PhplocTask;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Acquia\Orca\Utility\ConfigLoader;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Acquia\Orca\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Acquia\Orca\Utility\ConfigLoader|\Prophecy\Prophecy\ObjectProphecy $config
 * @property \Acquia\Orca\Report\CodeCoverageReportBuilder|\Prophecy\Prophecy\ObjectProphecy $reportBuilder
 * @coversDefaultClass \Acquia\Orca\Console\Command\Report\ReportCodeCoverageCommand
 */
class ReportCodeCoverageCommandTest extends CommandTestBase {

  private const DEFAULT_PATH = 'test/example';

  protected function setUp() {
    $this->reportBuilder = $this->prophesize(CodeCoverageReportBuilder::class);
    $this->config = $this->prophesize(ConfigLoader::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->orca
      ->exists(PhplocTask::JSON_LOG_PATH)
      ->willReturn(TRUE);
    $this->orca
      ->getPath(Argument::any())
      ->willReturnArgument();
  }

  /**
   * @covers ::__construct
   * @covers ::configure
   */
  public function testBasicConfiguration(): void {
    $command = $this->createCommand();

    $definition = $command->getDefinition();
    $arguments = $definition->getArguments();

    self::assertEquals('report:code-coverage', $command->getName(), 'Set correct name.');
    self::assertEquals(['coverage', 'cov'], $command->getAliases(), 'Set correct aliases.');
    self::assertNotEmpty($command->getDescription(), 'Set a description.');
    self::assertEquals(['path'], array_keys($arguments), 'Set correct arguments.');
    $path_argument = $definition->getArgument('path');
    self::assertTrue($path_argument->isRequired(), 'Required path argument.');
  }

  /**
   * @covers ::execute
   * @dataProvider providerHappyPath
   */
  public function testHappyPath(string $path, array $data, string $output): void {
    $this->reportBuilder
      ->build($path)
      ->shouldBeCalledOnce()
      ->willReturn($data);
    $this->createCommand();

    $this->executeCommand(['path' => $path]);

    self::assertEquals($output, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerHappyPath(): array {
    return [
      [
        self::DEFAULT_PATH, [
          ['Test', 1],
          ['Example', 2],
        ],
        // This ugly, un-expressive string is necessary because Git and Drupal
        // Drupal Code Sniffer can't agree on an acceptable multiline format.
        "\n Test    : 1 \n Example : 2 \n\n",
      ],
      ['.', [
        ['Lorem', 3],
        ['Ipsum', 4],
      ],
        "\n Lorem : 3 \n Ipsum : 4 \n\n",
      ],
    ];
  }

  /**
   * @covers ::execute
   */
  public function testPathNotFound(): void {
    $path = self::DEFAULT_PATH;
    $message = 'The "example" directory does not exist.';
    $this->reportBuilder
      ->build($path)
      ->willThrow(new OrcaDirectoryNotFoundException($message));
    $this->createCommand();

    $this->executeCommand(['path' => $path]);

    self::assertEquals("Error: {$message}\n", $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  /**
   * @covers ::execute
   */
  public function testNoCoverageData(): void {
    $this->reportBuilder
      ->build(self::DEFAULT_PATH)
      ->willThrow(FileNotFoundException::class);
    $this->createCommand();

    $this->executeCommand(['path' => self::DEFAULT_PATH]);

    self::assertEquals("Error: No code coverage data available.
Hint: Use the \"qa:static-analysis --phploc\" command to generate it.
", $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  /**
   * @covers ::execute
   */
  public function testInvalidCoverageData(): void {
    $this->reportBuilder
      ->build(self::DEFAULT_PATH)
      ->willThrow(ParseError::class);
    $this->createCommand();

    $this->executeCommand(['path' => self::DEFAULT_PATH]);

    self::assertEquals("Error: Invalid coverage data detected.
Hint: Use the \"qa:static-analysis --phploc\" command to regenerate it.
", $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  protected function createCommand(): Command {
    $report_builder = $this->reportBuilder->reveal();
    return new ReportCodeCoverageCommand($report_builder);
  }

}
