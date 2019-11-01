<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\FixtureCreator;
use Acquia\Orca\Fixture\SiteInstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Provides a command.
 */
class FixtureInstallSiteCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:install-site';

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The site installer.
   *
   * @var \Acquia\Orca\Fixture\SiteInstaller
   */
  private $siteInstaller;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\SiteInstaller $site_installer
   *   The site installer.
   */
  public function __construct(Fixture $fixture, SiteInstaller $site_installer) {
    $this->fixture = $fixture;
    $this->siteInstaller = $site_installer;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  protected function configure() {
    $this
      ->setAliases(['si'])
      ->setDescription('Installs the site')
      ->setHelp('Installs Drupal and enables company extensions.')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'Install without confirmation')
      ->addOption('profile', NULL, InputOption::VALUE_REQUIRED, 'The Drupal installation profile to use, e.g., "minimal". ("orca" is a pseudo-profile based on "testing", with the Toolbar module enabled and Seven as the admin theme)', FixtureCreator::DEFAULT_PROFILE);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->fixture->exists()) {
      $output->writeln("Error: No fixture exists at {$this->fixture->getPath()}.");
      return StatusCode::ERROR;
    }

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion(sprintf('Are you sure you want to drop all tables in the database and install a fresh site at %s? ', $this->fixture->getPath()));
    if (
      !$input->getOption('force')
      && ($input->getOption('no-interaction') || !$helper->ask($input, $output, $question))
    ) {
      return StatusCode::USER_CANCEL;
    }

    $profile = $input->getOption('profile');
    $this->siteInstaller->install($profile);
    return StatusCode::OK;
  }

}
