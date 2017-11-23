Feature: View User
  In order to a users profile
  As an organization admin
  I need to have access to the user profile page

  @admin @user @view-user @1011-0945
  Scenario: 1011-0945 Viewing user profile page
    Given I log in as a user with role "Admin"
    And I add a new user

    Then I view the user
    And I delete the User