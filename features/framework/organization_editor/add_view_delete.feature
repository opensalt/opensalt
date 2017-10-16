Feature: Adding, viewing and deleting a framework
  In order to confirm the application can add a new framework
  As an organization editor
  I need to see a all of the fields

  @incomplete @smoke @organization-editor @framework @add-framework
  Scenario: 1013-1408 An organization editor can add a framework
    Given I log in as a user with role "Editor"
    Then I should see the Create a new Framework
    And I click the "Create a new Framework" button
    Then I should see "LsDoc creation"
    When I create a "Draft" framework

  @incomplete @smoke @editor @framework @viewing-framework
  Scenario: 1013-1419 An organization editor can see a framework
    Given I log in as a user with role "Editor"
    And I should see "Draft" framework

  @incomplete @smoke @editor @framework @deleting-framework
  Scenario: 1013-1419 An organization editor can delete a framework
    Given I log in as a user with role "Editor"
    And I delete "Draft" framework