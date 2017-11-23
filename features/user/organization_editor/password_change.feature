Feature: Password Management
  In order to change my password
  As an organization editor
  I need to put in a new password

  @organization-editor @user @password @1016-1300
  Scenario: 1016-1300 Changing my Password
    Given I log in as a user with role "Editor"
    Then I change my password

