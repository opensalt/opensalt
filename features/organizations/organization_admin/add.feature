Feature: Add new Organization
  In order to a add a Organization
  As an organization admin
  I need to have access to the Organizations list page

  @incomplete @admin @org @add-org
  Scenario: 1011-1715 Adding new Organization
    Given I log in as a user with role "Admin"
    And I am on the Organizations list page
    And I click the "Add a new organization" button
    And I fill in the name
    And I click the "Add" button
    Then I should see name in the Organizations list