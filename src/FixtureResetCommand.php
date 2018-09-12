<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Provides the "fixture:reset" command.
 */
class FixtureResetCommand extends CommandBase {

  /**
   * Resets the test fixture to its base state.
   *
   * Restores the last committed state of the build directory from Git and
   * reinstalls Drupal.
   *
   * @command fixture:reset
   * @aliases reset
   *
   * @return \Robo\ResultData
   */
  public function execute($opts = []) {
    $confirm = $this->confirm('Are you sure you want to reset the test fixture?');
    if (!$confirm && !$opts['no-interaction']) {
      return Result::cancelled();
    }

    $git = $this->taskGitStack()
      ->dir(self::BUILD_DIR);
    return $this->collectionBuilder()
      ->addTask($git->exec('reset --hard ' . self::BASE_FIXTURE_BRANCH))
      ->addTask($git->exec('clean -fd'))
      ->addTask($this->installDrupal())
      ->run();
  }

}
