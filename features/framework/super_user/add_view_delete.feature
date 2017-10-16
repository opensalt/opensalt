Feature: Adding, viewing and deleting a framework
  In order to confirm the application can add a new framework
  As an Super-User
  I need to see a all of the fields

  @incomplete @smoke @super-user @framework @add-framework
  Scenario: 1016-1323 An Super-User can add a framework
    Given I log in as a user with role "Super-User"
    Then I should see the Create a new Framework
    And I click the "Create a new Framework" button
    Then I should see "LsDoc creation"
    When I create a "Draft" framework

  @incomplete @smoke @super-user @framework @viewing-framework
  Scenario: 1016-1324 An Super-User can see a framework
    Given I log in as a user with role "Super-User"
    And I should see "Draft" framework

  @incomplete @smoke @super-user @framework @deleting-framework
  Scenario: 1016-1325 An Super-User can delete a framework
    Given I log in as a user with role "Super-User"
    And I delete "Draft" framework