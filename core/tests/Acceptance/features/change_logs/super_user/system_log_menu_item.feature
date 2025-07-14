Feature: Change Log Interaction
  System Audit Log

  @0117-0729 @logs @ui @super-user
  Scenario: 0117-0729 User can view and use system history log- Login as a Super User
    Given I am logged in as an "Super-User"
    When I select "Manage system logs" from the menu dropdown
    Then I see "System Logs" in the title section
    And I see all of the data table elements for the system logs
