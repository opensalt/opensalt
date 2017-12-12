Feature: View User
  In order to a users profile
  As an super-user
  I need to have access to the user profile page

  @super-user @user @view-user @1016-1316 @duplicate
  Scenario: 1016-1316 Viewing user profile page
    Given I log in as a user with role "Super User"
    And I add a new user with "Super User" role

    Then I view the user
    And I delete the User