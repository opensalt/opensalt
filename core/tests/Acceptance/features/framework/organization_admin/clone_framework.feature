Feature: Cloning a framework

  @group-admin @framework @clone-framework @1033-1433
  Scenario: 1033-1433 A group-admin can clone a framework
    Given I log in as a user with role "Admin"
    Given I am on a framework page
    Then I should see the clone framework modal button
    When I click on clone framework modal button
    Then I should see the clone framework modal

    When I click on clone framework
    Then I should see a success message
