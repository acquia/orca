Feature: Example
  In order to prove that ORCA works properly and demonstrate how to use it
  As a project maintainer
  I need to be able to exercise it in an example Behat feature.

  @orca_public
  Scenario: Running a simple Behat test
    Given I add 2 and 2
    Then I get 4
