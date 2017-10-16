Feature: Adding, viewing and deleting a framework
  In order to confirm the application can add a new framework
  As an organization-admin
  I need to see a all of the fields

  @incomplete @smoke @organization-admin @framework @add-framework
  Scenario: 1016-1340 An organization-admin can add a framework
    Given I log in as a user with role "Admin"
    Then I should see the Create a new Framework
    And I click the "Create a new Framework" button
    Then I should see "LsDoc creation"
    When I create a "Draft" framework

  @incomplete @smoke @organization-admin @framework @viewing-framework
  Scenario: 1016-1341 An organization-admin can see a framework
    Given I log in as a user with role "Admin"
    And I should see "Draft" framework

  @incomplete @smoke @super-editor @framework @deleting-framework
  Scenario: 1016-1342 An organization-admin can delete a framework
    Given I log in as a user with role "Admin"
    And I delete "Draft" framework