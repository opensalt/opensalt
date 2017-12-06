Feature: Edit an existing User
  In order to a edit a user
  As an super-user
  I need to have access to the user profile page

  @super-user @user @edit-user @1016-1312 @duplicate
  Scenario: 1016-1312 Edit a user in User List
    Given I log in as a user with role "Super User"
    And I add a new user with "Super User" role

    Then I edit a user profile
    | newuser@somewhere.com |

    Then I delete the User