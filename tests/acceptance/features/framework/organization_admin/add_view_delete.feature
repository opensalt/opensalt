Feature: Adding, viewing and deleting a framework
  In order to confirm the application can add a new framework
  As an organization-admin
  I need to see a all of the fields

  @organization-admin @framework @add-framework @1016-1340 @duplicate
  Scenario: 1016-1340 An organization-admin can add a framework
    Given I log in as a user with role "Admin"
    Then I should see "Create a new Framework" button

    When I click the "Create a new Framework" button
    Then I should see "LsDoc creation"

    When I create a "Draft" framework
    Then I should see the framework
    And I delete the framework

