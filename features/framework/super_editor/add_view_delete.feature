Feature: Adding, viewing and deleting a framework
  In order to confirm the application can add a new framework
  As an super editor
  I need to see a all of the fields

  @incomplete @smoke @super-editor @framework @add-framework
  Scenario: 1016-1323 An super editor can add a framework
    Given I log in as a user with role "Super-Editor"
    Then I should see the Create a new Framework
    And I click the "Create a new Framework" button
    Then I should see "LsDoc creation"
    When I create a "Draft" framework

  @incomplete @smoke @super-editor @framework @viewing-framework
  Scenario: 1016-1324 An super editor can see a framework
    Given I log in as a user with role "Super-Editor"
    And I should see "Draft" framework

  @incomplete @smoke @super-editor @framework @deleting-framework
  Scenario: 1016-1325 An super editor can delete a framework
    Given I log in as a user with role "Super-Editor"
    And I delete "Draft" framework