Feature: View Organization
  In order to an organization
  As an organization admin
  I need to have access to the Organization profile page

  @incomplete @admin @org @view-org
  Scenario: 1011-1737 Viewing organization profile page
    Given I log in as a user with role "Admin"
    And I am on the Organizations list page
    And I click on "show" button for "Unknown"
    Then I should see the following:
      | Unknown |