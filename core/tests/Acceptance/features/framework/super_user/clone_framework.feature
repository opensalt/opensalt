Feature: Cloning a framework

  @super-user @framework @clone-framework @1036-1436 @duplicate
  Scenario: 1036-1436 A Super-User can clone a framework
    Given I log in as a user with role "Super-User"
    Given I am on a framework page
    Then I should see the clone framework modal button
    When I click on clone framework modal button
    Then I should see the clone framework modal

    When I click on clone framework
    Then I should see a success message
