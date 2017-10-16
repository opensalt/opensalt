Feature: Edit an existing User
  In order to a edit a user
  As an super-user
  I need to have access to the user profile page

  @incomplete @super-user @user @edit-user
  Scenario Outline: 1011-1058 Edit a user in User List
    Given I log in as a user with role "Super-User"
    And I am on the User list page
    And I click on "edit" button for user "<user>"
    And I fill in username with "<userNew>"
    And I click on "Save" button
    Then I should see "<userNew>" in user list

    Examples:
      | user   | userNew  |
      | Tester | Tester 2 |