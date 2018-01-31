Feature: Change Log Interaction
  System Audit Log

  @0117-0730 @logs @ui @super-user
  Scenario: 0117-0730 User can view and use system history log of the new user added, new org added, user updated, org updated- Login as a Super User
    Given I am logged in as an "Super-User"

    When I add a new user
    And I change the user's email address
    And I delete the User

    And I add a Organization
    And I change the name of the Organization
    And I delete the Organization

    And I go to the system logs
    Then I should see the add of the user in the log
    And I should see the update of the user in the log
    And I should see the delete of the user in the log
    And I should see the add of the organization in the log
    And I should see the update of the organization in the log
    And I should see the delete of the organization in the log
