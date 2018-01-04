Feature: List of Users
  In order to see List of Users
  As an organization admin
  I need to have access to the User list page

  @admin @user @view-user-list @1011-0930
  Scenario: 1011-0930 Viewing User list page
    Given I log in as a user with role "Admin"
    And I am on the User list page




