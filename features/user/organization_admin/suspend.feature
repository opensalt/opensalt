Feature: Suspend an existing User
  In order to a suspend a user
  As an organization admin
  I need to have access to the user profile page

  @admin @user @suspend @1011-1416
  Scenario: 1011-1416 Suspend a user in User List
    Given I log in as a user with role "Admin"
    And I add a new user

    Then I suspend the user
    And I delete the User