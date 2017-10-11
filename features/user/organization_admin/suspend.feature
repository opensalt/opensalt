Feature: Suspend an existing User
  In order to a suspend a user
  As an organization admin
  I need to have access to the user profile page

  @incomplete @admin @user @suspend
  Scenario Outline: 1011-1416 Suspend a user in User List
    Given I log in as a user with role "Admin"
    And I am on the User list page
    And I find user "<user>"
    And I Suspend "<user>"
    Then I should see "<user>" can be unsuspended

    Examples:
      | user     |
      | Tester 1 |