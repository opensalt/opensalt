Feature: Reinstate an existing User
  In order to a edit a user
  As an organization admin
  I need to have access to the user profile page

  @admin @user @reinstate @1011-1417
  Scenario: 1011-1417 Reinstate a user in User List
    Given I log in as a user with role "Admin"
    And I add a new user
    And I suspend the user

    Then I reinstate the user
    And I delete the User