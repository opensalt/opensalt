Feature: Adding, viewing and deleting a framework
  In order to confirm the application can add a new framework
  As an Super-User
  I need to see a all of the fields

  @super-user @framework @add-framework @1016-1323 @duplicate
  Scenario: 1016-1323 An Super-User can add a framework
    Given I log in as a user with role "Super-User"
    Then I should see "Create a new Framework" button

    When I click the "Create a new Framework" button
    Then I should see "LsDoc creation"
    Then I should see licence drop-down

    When I create a "Draft" framework
    Then I should see the framework
    And I delete the framework
