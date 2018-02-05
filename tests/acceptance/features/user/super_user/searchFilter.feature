Feature: Search filter
  In order to see List of Users
  As an super-user
  I need to have access to the User list page

  @super-user @user @view-user-list @1016-1317 @duplicate
  Scenario: 1016-1317 Viewing User list page with search filters
    Given I log in as a user with role "Super-User"
    And I am on the User list page

    Then I search organization and role type