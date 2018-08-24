Feature: Fetch a list of frameworks from a creator from the API
  In order to display a list of frameworks from a creator
  As another system
  I can fetch a list of frameworks by creator

  @api @0606-1251
  Scenario: 0606-1251
    Given I log in as a user with role "Editor"
    And I create a framework with a remembered creator

    When I fetch a list of frameworks by the remembered creator
    Then the created framework will be in the list

    Then I delete the framework
