Feature: A framework with high UTF8 characters can be loaded
  In order to copy a framework from another server
  As an organization-editor
  I need to upload a CASE file of the framework

  @organization-editor @case-file @utf8 @framework @1116-1621
  Scenario: 1116-1621 A CASE file can contain non BMP UTF8 characters
    Given I log in as a user with role "Editor"
    And I am on the homepage
    When I click "Import framework"
    Then I should see the import dialogue
    When I click "Import CASE file"
    And I upload the utf8-test CASE file
    And I go to the uploaded framework
    And I download the framework CASE file
    Then the downloaded framework should match the uploaded one
    And I delete the framework

