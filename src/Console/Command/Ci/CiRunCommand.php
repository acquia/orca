<?php

namespace Acquia\Orca\Console\Command\Ci;

use Acquia\Orca\Domain\Ci\CiJobFactory;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Event\CiEvent;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use Acquia\Orca\Options\CiRunOptionsFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Provides a command.
 */
class CiRunCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ci:run';

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
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  private $eventDispatcher;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Ci\CiJobFactory $job_factory
   *   The CI job factory.
   * @param \Acquia\Orca\Options\CiRunOptionsFactory $ci_run_options_factory
   *   The CI run options factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcher
   *   The event dispatcher service.
   */
  public function __construct(CiJobFactory $job_factory, CiRunOptionsFactory $ci_run_options_factory, EventDispatcher $eventDispatcher) {
    $this->ciJobFactory = $job_factory;
    $this->ciRunOptionsFactory = $ci_run_options_factory;
    $this->eventDispatcher = $eventDispatcher;
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
    $data = [];
    try {
      $data = [
        'job' => $input->getArgument('job'),
        'phase' => $input->getArgument('phase'),
        'sut' => $input->getArgument('sut'),
      ];
      $options = $this->ciRunOptionsFactory->create($data);
      // Initialize as failure.
      $data['status'] = 'FAIL';
      $job = $this->ciJobFactory->create($options->getJob());
      $data['version'] = $job->getDrupalCoreVersion();
      $job->run($options);

    }
    catch (OrcaInvalidArgumentException $e) {
      $output->writeln("Error: {$e->getMessage()}");
      return StatusCodeEnum::ERROR;
    }
    catch (\Exception $e) {
      $event = new CiEvent($data);
      $this->eventDispatcher->dispatch($event, CiEvent::NAME);
      return StatusCodeEnum::ERROR;
    }

    if ($options->getPhase()->getValue() === 'script') {
      if ($job->exitEarly() === TRUE) {
        print("No data to Google sheet as test is skipped.");
      }
      else {
        $data['status'] = 'PASS';
        $event = new CiEvent($data);
        $this->eventDispatcher->dispatch($event, CiEvent::NAME);
      }

    }

    return StatusCodeEnum::OK;
  }

}
