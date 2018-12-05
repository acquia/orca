Feature: Example
  In order to prove that ORCA works properly and demonstrate how to use it
  As a project maintainer
  I need to be able to exercise it in an example Behat feature.

  @orca_public
  Scenario: Visiting the home page
    Given I visit the homepage
    Then I get an HTTP 200 status code
