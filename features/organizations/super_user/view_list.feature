Feature: List of Organizations
  In order to see List of Organizations
  As an Super-User
  I need to have access to the User list page

  @super-user @org @view-org-list @1011-1740
  Scenario: 1011-1740 Super-User Viewing Organization list page
    Given I log in as a user with role "Super-User"
    And I am on the Organizations list page
    Then I should see the following:
      | Id     |
      | name   |
      | Action |