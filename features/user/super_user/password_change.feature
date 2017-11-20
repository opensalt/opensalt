Feature: Password Management
  In order to change my password
  As an super user
  I need to put in a new password

  @incomplete @super-user @user @password
  Scenario: 1016-1313 Changing my Password
    Given I log in as a user with role "Super-User"
    And I am on the homepage
    When I click "Signed in as" Role
    Then I should see dropdown menu
    When I click "Change Password"
    And I fill in my old password
    And I fill in my new password twice
    And I click the "Change Password" button
    Then I should see "Your password has been changed."

