Feature: Delete User
  In order to a delete a user
  As an organization admin
  I need to have access to the user list page

  @incomplete @admin @user @delete-user
  Scenario: 1011-0953 Deleting a user in User List
    Given I log in as a user with role "Admin"
    And I am on the User list page
    And I click on "show" button for user "3"
    And I click on "Delete" button
    Then I should not see user "3"
