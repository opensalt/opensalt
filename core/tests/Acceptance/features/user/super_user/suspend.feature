Feature: Suspend an existing User
  In order to a suspend a user
  As an super-user
  I need to have access to the user profile page

  @super-user @user @suspend @1016-1315 @duplicate
  Scenario: 1016-1315 Suspend a user in User List
    Given I log in as a user with role "Super User"
    Then I add a new user with "Super User" role

    Then I suspend the user

    Then I delete the User
