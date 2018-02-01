Feature: Copying framework content to another framework

  @organization-admin @framework @copy-framework
  Scenario: 1013-1408 An organization-admin can copy a framework
    Given I log in as a user with role "Admin"
    Given I am on a framework page
    Then I should see the copy framework modal button
    When I click on copy framework modal button
    Then I should see the copy framework modal

    When I click on copy framework
    Then I should see a success message

