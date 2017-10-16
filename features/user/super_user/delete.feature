Feature: Delete User
  In order to a delete a user
  As an super-user
  I need to have access to the user list page

  @incomplete @super-user @user @delete-user
  Scenario: 1011-1246 Deleting a user in User List
    Given I log in as a user with role "Super-User"
    And I am on the User list page
    And I click on "show" button for user "3"
    And I click on "Delete" button
    Then I should not see user "3"
