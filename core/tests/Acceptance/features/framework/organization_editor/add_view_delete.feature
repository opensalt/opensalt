Feature: Adding, viewing and deleting a framework
  In order to confirm the application can add a new framework
  As a group editor
  I need to see a all of the fields

  @group-editor @framework @add-framework @1013-1408
  Scenario: 1013-1408 A group editor can add a framework
    Given I log in as a user with role "Editor"
    Then I should see "Create a new Framework" button

    When I click the "Create a new Framework" button
    Then I should see "Create New Framework Package"

    When I create a "Draft" framework
    Then I should see the framework
    And I delete the framework
