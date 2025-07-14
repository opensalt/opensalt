Feature: Change Log Interaction
  System Audit Log Export

  @0117-0733 @logs @ui @super-user
  Scenario: 0117-0733 User can have a copy in csv with all of the history data - Login as Super User
    Given I am logged in as an "Super-User"
    #And the system log has some changes
    When I add a new user
    And I delete the User
    And I go to the system logs
    And I download the system log export CSV
    Then I can see the data in the CSV matches the data in the system log
