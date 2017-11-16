Feature: Reinstate an existing User
  In order to a edit a user
  As an super-user
  I need to have access to the user profile page

  @super-user @user @reinstate @1016-1314
  Scenario: 1016-1314 Reinstate a user in User List
    Given I log in as a user with role "Super User"
    Then I add a new user with "Super User" role
    And I suspend the user

    Then I reinstate the user
    And I delete the User