@api
Feature: Example
  In order to prove that ORCA works properly and demonstrate how to use it
  As a project maintainer
  I need to be able to exercise it in an example Behat feature.

  @orca_public
  Scenario: Visiting the home page
    Given I visit the homepage
    Then I should get an HTTP 200 status code

  @orca_ignore
  Scenario: Ignoring a scenario
    Given I tag a scenario @orca_ignore
    When I run ORCA tests
    Then the tagged scenario should not be run

  @javascript
  Scenario: Exercising ChromeDriver
    Given I am logged in as a user with the "authenticated user" role
    And I visit the homepage
    Then I should get an HTTP 200 status code
