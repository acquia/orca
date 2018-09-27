<?php

namespace Acquia\Orca\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Provides the "fixture:destroy" command.
 */
class FixtureDestroyCommand extends CommandBase {

  /**
   * Destroys the test fixture.
   *
   * Deletes the entire site build directory.
   *
   * @command fixture:destroy
   * @aliases destroy
   *
   * @return \Robo\Collection\CollectionBuilder|int
   */
  public function execute(array $options = []) {
    $confirm = $this->confirm('Are you sure you want to destroy the test fixture?');
    if (!$confirm && !$options['no-interaction']) {
      return Result::EXITCODE_USER_CANCEL;
    }

    return $this->collectionBuilder()
      ->addTaskList([
        $this->dropDrupalDatabase(),
        $this->fixFilePermissions(),
        $this->taskDeleteDir($this->buildPath()),
      ]);
  }

}
