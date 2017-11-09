Feature: List of Organizations
  In order to see List of Organizations
  As an organization admin
  I need to have access to the User list page

  @incomplete @admin @org @view-org-list
  Scenario: 1011-1740 Viewing Organization list page
    Given I log in as a user with role "Admin"
    And I am on the Organizations list page
    Then I should see the following:
      | Id   |
      | name |