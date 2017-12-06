Feature: The framework can be uploaded
  In order to copy a framework from another server
  As an organization-editor
  I need to upload a CASE file of the framework

  @organization-editor @framework @case-file @json @1013-1201
  Scenario: 1013-1201 A CASE file can be uploaded and downloaded
    Given I log in as a user with role "Editor"
    And I am on the homepage
    When I click "Import framework"
    Then I should see the import dialogue
    When I click "Import CASE file"
    And I upload the empty remote CASE file
    And I go to the uploaded framework
    And I download the framework CASE file
    Then the downloaded framework should match the uploaded one
    And I delete the framework

