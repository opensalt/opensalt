Feature: Reinstate an existing User
  In order to a edit a user
  As an super-user
  I need to have access to the user profile page

  @incomplete @super-user @user @reinstate
  Scenario: 1016-1248 Reinstate a user in User List
    Given I log in as a user with role "Super-User"
    And I am on the User list page
    And I click the "Add a new user" button
    And I fill in the username
    And I fill in the password
    And I check "Editor" role
    And I select "Unknown" Org
    And I click the "Add" button
    Then I should see username in the User list