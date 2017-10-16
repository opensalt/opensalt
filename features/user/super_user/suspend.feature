Feature: Suspend an existing User
  In order to a suspend a user
  As an super-user
  I need to have access to the user profile page

  @incomplete @super-user @user @suspend
  Scenario Outline: 1016-1249 Suspend a user in User List
    Given I log in as a user with role "Super-User"
    And I am on the User list page
    And I find user "<user>"
    And I Suspend "<user>"
    Then I should see "<user>" can be unsuspended

    Examples:
      | user     |
      | Tester 1 |