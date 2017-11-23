Feature: The framework can be uploaded as CSV
  In order to copy a framework from csv file
  As an Super-User
  I need to upload a CSV file of the framework

  @incomplete @super-user @framework @case-file @csv @1016-1328
  Scenario: 1016-1328 A CSV file can be uploaded and downloaded
    Given I log in as a user with role "Super-User"
    And I am on the homepage
    When I click "Import framework"
    Then I should see the import dialogue
    When I click "Import Spreadsheet file"
    And I upload the ccsso_ela_item file
    And I go to the uploaded framework
    And I download the framework csv file
    Then the downloaded csv framework should match the uploaded one
    And I delete the framework
