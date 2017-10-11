Feature: View User
  In order to a users profile
  As an organization admin
  I need to have access to the user profile page

  @admin @user @view-user
  Scenario: 1011-0945 Viewing user profile page
    Given I log in as a user with role "Admin"
    And I am on the User list page
    And I click on "show" button for "3"
    Then I should see the following:
      | TEST:Rau, O'Keefe and Hane |
      | TEST:EDITOR:eloise86       |
      | Editor                     |