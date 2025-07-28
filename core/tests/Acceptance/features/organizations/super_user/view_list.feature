Feature: List of Groups
  In order to see List of Groups
  As an Super-User
  I need to have access to the User list page

  @super-user @group @view-group-list @1011-1740
  Scenario: 1011-1740 Super-User Viewing Group list page
    Given I log in as a user with role "Super-User"
    And I am on the Groups list page
    Then I should see the following:
      | Id     |
      | name   |
      | Action |
