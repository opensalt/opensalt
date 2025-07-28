Feature: Add new User
  In order to a add a user
  As a group admin
  I need to have access to the user profile page

  @group-admin @user @add-user @1011-1415
  Scenario: 1011-1415 Adding new user
    Given I log in as a user with role "Admin"
    Then I add a new user
    And  I delete the User
