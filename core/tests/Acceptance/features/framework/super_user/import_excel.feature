Feature: The framework can be uploaded as Excel
  In order to copy a framework from excel file
  As an organization-editor
  I need to upload a Excel file of the framework

  @super-user @framework @excel @duplicate @1013-1214
  Scenario: 1013-1214 A Excel file can be uploaded and downloaded
    Given I log in as a user with role "Super-User"
    And I am on the homepage
    Then I should see "Import framework" button

    When I click "Import framework"
    Then I should see the import dialogue
    When I click "Import Spreadsheet"
    And I upload an excel file
    And I visit the uploaded framework
    Then I should see the framework created with the spreadsheet data

    Then I delete the framework
