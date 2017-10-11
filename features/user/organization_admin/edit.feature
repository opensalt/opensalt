Feature: Edit an existing User
  In order to a edit a user
  As an organization admin
  I need to have access to the user profile page

  @incomplete @admin @user @edit-user
  Scenario: 1011-1058 Deleting a user in User List
    Given I log in as a user with role "Admin"
    And I am on the User list page
    And I click on "edit" button for user "3"
    And I fill in username with "<user>"
    And I click on "Save" button
    Then I should see "<user>" in user list