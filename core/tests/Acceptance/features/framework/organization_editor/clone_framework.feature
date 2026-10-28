Feature: Cloning a framework

  @group-editor @framework @clone-framework @1032-1432
  Scenario: 1032-1432 A group editor can clone a framework
    Given I log in as a user with role "Editor"
    Given I am on a framework page
    Then I should see the clone framework modal button
    When I click on clone framework modal button
    Then I should see the clone framework modal

    When I click on clone framework
    Then I should see a success message
