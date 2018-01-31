Feature: Change Log Interaction
  Search form on the System Audit Log

  @0117-0731 @logs @ui @super-user
  Scenario: 0117-0731 Searching for an item in the search form shows the table of data elements with those item rows - Logged in as  Super User
    Given I am logged in as an "Super-User"
    When I add a new user
    And I delete the User
    And I go to the system logs
    And I enter "added" in the sytem log search field
    Then I should only see rows with "added" in the system log data table
