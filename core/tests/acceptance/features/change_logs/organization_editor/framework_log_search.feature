Feature: Change Log Interaction
  Search form on the Log view page

  @0117-0707 @logs @ui @organization-editor
  Scenario: 0117-0707 Searching for an item in the search form shows the table of data elements with those item rows - Logged in as Organization Editor
    Given I am logged in as an "Editor"
    When I create a framework
    And I add the item "First trial Item"
    And I edit the fields in a item
      | Abbreviated statement | 1st trial Item |
    And I edit the fields in a item
      | Abbreviated statement | First trial Item |
    And I select Log View
    And I enter "trial" in the log search field
    Then I should only see rows with "trial" in the log data table
    And I delete the framework
