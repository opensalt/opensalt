Feature: Password Management
  In order to change my password
  As an super editor
  I need to put in a new password

  @super-editor @user @password @1016-1301
  Scenario: 1016-1301 Changing my Password
    Given I log in as a user with role "Super-Editor"
    Then I change my password


