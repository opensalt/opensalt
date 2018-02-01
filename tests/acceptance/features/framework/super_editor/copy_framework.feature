Feature: Copying framework content to another framework

  @super-user @framework @copy-framework @1016-1323 @duplicate
  Scenario: An Super-Editor can copy a framework
    Given I log in as a user with role "Super-Editor"
    Given I am on a framework page
    Then I should see the copy framework modal button
    When I click on copy framework modal button
    Then I should see the copy framework modal

    When I click on copy framework
    Then I should see a success message

