Feature: Add new User
  In order to a add a user
  As an organization admin
  I need to have access to the user profile page

  @super-user @user @add-user @1016-1245
  Scenario: 1016-1245 Adding new user
    Given I log in as a user with role "Super User"
    Then I add a new user with "Super User" role

    Then I delete the User