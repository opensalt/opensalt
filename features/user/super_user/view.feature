Feature: View User
  In order to a users profile
  As an super-user
  I need to have access to the user profile page

  @incomplete @super-user @user @view-user
  Scenario: 1016-1249 Viewing user profile page
    Given I log in as a user with role "Super-User"
    And I am on the User list page
    And I click on "show" button for "3"
    Then I should see the following:
      | TEST:Rau, O'Keefe and Hane |
      | TEST:EDITOR:eloise86       |
      | Editor                     |