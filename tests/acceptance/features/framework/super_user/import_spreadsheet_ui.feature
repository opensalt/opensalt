Feature: The import spreadsheet UI
  @incomplete @super-user @framework @case-file @excel @0486-0586 @duplicate
  Scenario: 0486-0586 Import spreadsheet UI
    Given I log in as a user with role "Super-User"
    And I am on the homepage
    When I click "Import framework"
    Then I should see the import dialogue
    When I click "Import Spreadsheet file"
    And I download the template excel file
    And I download the sample excel file
    When I click "Cancel"