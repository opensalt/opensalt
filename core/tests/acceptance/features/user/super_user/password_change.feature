Feature: Password Management
  In order to change my password
  As an super user
  I need to put in a new password

  @super-user @user @password @1016-1313
  Scenario: 1016-1313 Changing my Password
    Given I log in as a user with role "Super-User"
    Then I change my password

