Feature: Click Reject Button
  In order to reject a user
  As an super-user
  I need to have access to the user profile page

  @super-user @user @reinstate @1828-0126
  Scenario: 1828-0126 Reject a user in User List
    Given I log in as a user with role "Super User"
    And I add a new user with "Super User" role
    And I reject the new user
    Then I delete the User
