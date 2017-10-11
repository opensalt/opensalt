Feature: Add new User
  In order to a add a user
  As an organization admin
  I need to have access to the user profile page

  @incomplete @admin @user @add-user
  Scenario: 1011-1415 Adding new user
    Given I log in as a user with role "Admin"
    And I am on the User list page
    And I click the "Add a new user" button
    And I fill in the username
    And I fill in the password
    And I check "Editor" role
    And I select "Unknown" Org
    And I click the "Add" button
    Then I should see username in the User list