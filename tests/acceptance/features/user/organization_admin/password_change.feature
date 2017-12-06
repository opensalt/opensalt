Feature: Password Management
  In order to change my password
  As an organization admin
  I need to put in a new password

  @admin @user @password @1011-0905
  Scenario: 1011-0905 Changing my Password
    Given I log in as a user with role "Admin"
    Then I change my password

