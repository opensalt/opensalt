Feature: Copying framework content to another framework

  @organization-editor @framework @copy-framework @1032-1432
  Scenario: 1032-1432 An organization editor can copy a framework
    Given I log in as a user with role "Editor"
    Given I am on a framework page
    Then I should see the copy framework modal button
    When I click on copy framework modal button
    Then I should see the copy framework modal

    When I click on copy framework
    Then I should see a success message

