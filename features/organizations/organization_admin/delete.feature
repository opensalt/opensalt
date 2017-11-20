Feature: Delete Organization
  In order to a delete a Organization
  As an organization admin
  I need to have access to the Organization list page

  @incomplete @admin @org @delete-org
  Scenario: 1011-1726 Deleting a Organization in Organization List
    Given I log in as a user with role "Admin"
    And I am on the Organization list page
    And I click on "show" button for organization "3"
    And I click on "Delete" button
    Then I should not see organization "3"