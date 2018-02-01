Feature: Change Log Interaction
  Data Table that has at least 25 changes

  @0117-0732 @logs @ui @super-user
  Scenario: 0117-0732 User should see 25 rows in the data table when 25 selected from the Show Entries selected - Logged in as  Super User
    Given I am logged in as an "Super-User"
    And the system log has at least 25 changes
    When I add a new user
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I change the user's email address
    And I delete the User

    And I go to the system logs
    And I select 25 from the "Show entries" dropdown
    Then I should see 25 rows in the system log data table
