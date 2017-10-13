Feature: Adding, viewing and deleting a framework
  In order to confirm the application can add a new framework
  As an editor user
  I need to see a all of the fields

  @smoke @editor @framework @add-framework
  Scenario: 1013-1408 An editor user can add a framework
    Given I log in as a user with role "Editor"
    Then I should see the Create a new Framework
    And I click the "Create a new Framework" button
    Then I should see "LsDoc creation"
    When I create a "Draft" framework

  @smoke @editor @framework @viewing-framework
  Scenario: 1013-1419 An editor user can see a framework
    Given I log in as a user with role "Editor"
    And I should see "Draft" framework

  @smoke @editor @framework @deleting-framework
  Scenario: 1013-1419 An editor user can delete a framework
    Given I log in as a user with role "Editor"
    And I delete "Draft" framework