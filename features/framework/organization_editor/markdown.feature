Feature: A framework with markdown can be loaded
  In order to add styling to statements
  As an organization editor
  I need to be able to use markdown in the full statement

  @organization-editor @case-file @markdown @1107-0825
  Scenario: 1107-0825 A CASE file can contain markdown syntax
    Given I log in as a user with role "Editor"
    And I am on the homepage
    When I click "Import framework"
    Then I should see the import dialogue
    When I click "Import CASE file"
    And I upload the markdown CASE file
    And I go to the uploaded framework
    Then I should see math in the framework
    And I should see a table in the framework

