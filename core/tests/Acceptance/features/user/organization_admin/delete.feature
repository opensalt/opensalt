Feature: Delete User
  In order to a delete a user
  As an organization admin
  I need to have access to the user list page

  @admin @user @delete-user @1011-0953
  Scenario: 1011-0953 Deleting a user in User List
    Given I log in as a user with role "Admin"
    And I add a new user

    Then  I delete the User
