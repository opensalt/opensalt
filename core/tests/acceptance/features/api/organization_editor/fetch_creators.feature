Feature: Fetch creators from API
  In order to display a list of creators
  As another system
  I can fetch a list of creators

  @api @0606-1250
  Scenario: 0606-1250
    Given I log in as a user with role "Editor"
    And I create a framework with a remembered creator

    When I fetch a list of creators
    Then the remembered creator will be in the list

    Then I delete the framework
