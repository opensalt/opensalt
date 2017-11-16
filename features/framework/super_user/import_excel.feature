Feature: The framework can be uploaded as Excel
  In order to copy a framework from excel file
  As an Super-User
  I need to upload a Excel file of the framework

  @incomplete @super-user @framework @case-file @excel @1016-1329
  Scenario: 1016-1329 A Excel file can be uploaded and downloaded
    Given I log in as a user with role "Super-User"
    And I am on the homepage
    When I click "Import framework"
    Then I should see the import dialogue
    When I click "Import Spreadsheet file"
    And I upload the ccsso_ela file
    And I go to the uploaded framework
    And I download the framework excel file
    Then the downloaded excel framework should match the uploaded one
    And I delete the framework
