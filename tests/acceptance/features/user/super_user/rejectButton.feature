Feature: Click Reject Button
  In order to a add a user
  As an super-user
  I need to have access to the user profile page

  @super-user @user @reinstate @1011-1415 @duplicate
  Scenario: 1011-1415 Reinstate a user in User List
    Given I log in as a user with role "Super User"
    Then I add a new user with "Super User" role
    And I reject the new user

    And I delete the User
