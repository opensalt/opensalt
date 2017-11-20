Feature: Adding, viewing and deleting a framework
  In order to confirm the application can add a new framework
  As an super editor
  I need to see a all of the fields

  @super-editor @framework @add-framework
  Scenario: 1016-1323 An super editor can add a framework
    Given I log in as a user with role "Super-Editor"
    Then I should see "Create a new Framework" button

    When I click the "Create a new Framework" button
    Then I should see "LsDoc creation"

    When I create a "Draft" framework
    Then I should see the framework
    And I delete the framework