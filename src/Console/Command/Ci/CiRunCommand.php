<?php

namespace Acquia\Orca\Console\Command\Ci;

use Acquia\Orca\Domain\Ci\CiJobFactory;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use Acquia\Orca\Options\CiRunOptionsFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class CiRunCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ci:run';

  /**
   * Jobs allowed to fail due to being unstable or broken.
   *
   * @var string[]
   */
  private static $allowedFailures = [
    'INTEGRATED_TEST_ON_NEXT_MINOR',
  ];

  /**
   * The CI job factory.
   *
   * @var \Acquia\Orca\Domain\Ci\CiJobFactory
   */
  private $ciJobFactory;

  /**
   * The CI run options factory.
   *
   * @var \Acquia\Orca\Options\CiRunOptionsFactory
   */
  private $ciRunOptionsFactory;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Ci\CiJobFactory $job_factory
   *   The CI job factory.
   * @param \Acquia\Orca\Options\CiRunOptionsFactory $ci_run_options_factory
   *   The CI run options factory.
   */
  public function __construct(CiJobFactory $job_factory, CiRunOptionsFactory $ci_run_options_factory) {
    $this->ciJobFactory = $job_factory;
    $this->ciRunOptionsFactory = $ci_run_options_factory;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['run'])
      ->setDescription('Runs an ORCA CI job phase')
      ->addArgument('job', InputArgument::REQUIRED, $this->getJobArgumentDescription())
      ->addArgument('phase', InputArgument::REQUIRED, $this->getPhaseArgumentDescription())
      ->addArgument('sut', InputArgument::REQUIRED, 'The system under test (SUT) in the form of its package name, e.g., "drupal/example"');
  }

  /**
   * Gets the "job" argument description.
   *
   * @return string
   *   The description text.
   */
  private function getJobArgumentDescription(): string {
    return $this->formatArgumentDescription('The job name', CiJobEnum::descriptions());
  }

  /**
   * Gets the "phase" argument description.
   *
   * @return string
   *   The description text.
   */
  private function getPhaseArgumentDescription(): string {
    return $this->formatArgumentDescription('The phase name', CiJobPhaseEnum::descriptions());
  }

  /**
   * Formats the description text for a given argument.
   *
   * @param string $summary
   *   The one-line description summary.
   * @param array $values
   *   An array of allowable argument values as keys with their descriptions as
   *   array values.
   *
   * @return string
   *   The formatted text.
   */
  private function formatArgumentDescription(string $summary, array $values): string {
    $description = ["{$summary}:"];
    foreach ($values as $key => $value) {
      $description[] = "- {$key}: {$value}";
    }
    return implode(PHP_EOL, array_merge($description));
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    try {
      $options = $this->ciRunOptionsFactory->create([
        'job' => $input->getArgument('job'),
        'phase' => $input->getArgument('phase'),
        'sut' => $input->getArgument('sut'),
      ]);
      $job = $this->ciJobFactory->create($options->getJob());
      $job->run($options);
    }
    catch (OrcaInvalidArgumentException $e) {
      $output->writeln("Error: {$e->getMessage()}");
      return StatusCodeEnum::ERROR;
    }
    catch (\Throwable $throwable) {
      if (in_array($input->getArgument('job'), self::$allowedFailures)) {
        $output->writeln($throwable->getMessage());
        $output->writeln('This job is allowed to fail and will report as passing.');
        return StatusCodeEnum::OK;
      }
      throw $throwable;
    }
    return StatusCodeEnum::OK;
  }

}
