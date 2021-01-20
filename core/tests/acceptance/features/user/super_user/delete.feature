Feature: Delete User
  In order to a delete a user
  As an super-user
  I need to have access to the user list page

  @super-user @user @delete-user @1016-1311 @duplicate
  Scenario: 1016-1311 Deleting a user in User List
    Given I log in as a user with role "Super-User"
    And I add a new user with "Super User" role

    Then I delete the User