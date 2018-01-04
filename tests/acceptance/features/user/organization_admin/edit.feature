Feature: Edit an existing User
  In order to a edit a user
  As an organization admin
  I need to have access to the user profile page

  @admin @user @edit-user @1011-1058
  Scenario: 1011-1058 Edit a user in User List
    Given I log in as a user with role "Admin"
    And I add a new user

    Then I edit a user profile
      | newuser@somewhere.com |

    And  I delete the User