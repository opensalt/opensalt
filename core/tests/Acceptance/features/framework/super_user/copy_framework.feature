Feature: Copying framework content to another framework

  @super-user @framework @copy-framework @1036-1436 @duplicate
  Scenario: 1036-1436 An Super-User can copy a framework
    Given I log in as a user with role "Super-User"
    Given I am on a framework page
    Then I should see the copy framework modal button
    When I click on copy framework modal button
    Then I should see the copy framework modal

    When I click on copy framework
    Then I should see a success message

  @super-user @framework @copy-framework @1036-1436 @duplicate
  Scenario: 1036-1436 An Super-User can copy a framework
    Given I log in as a user with role "Super-User"
    Given I am on a framework page
    Then I should see the copy framework modal button
    When I click on copy framework modal button
    Then I should see the copy framework modal

    When I click on copy from button
    When I click on copy framework
    Then I should see a success message

