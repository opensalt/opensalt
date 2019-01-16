Feature: The framework can be uploaded as Excel
  In order to copy a framework from excel file
  As an organization-editor
  I need to upload a Excel file of the framework

  @organization-editor @framework @excel
  Scenario: 1013-1214 A Excel file can be uploaded and downloaded
    Given I log in as a user with role "Editor"
    And I am on the homepage
    Then I should see "Import framework" button

    When I click "Import framework"
    Then I should see the import dialogue
    When I click "/html/body/div[1]/main/div[3]/div/div/div[2]/div[1]/div/ul/li[2]/a"
    And I upload an excel file
    And I visit the uploaded framework
    Then I should see the framework created with the spreadsheet data
