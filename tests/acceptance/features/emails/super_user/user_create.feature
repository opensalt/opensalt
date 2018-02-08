Feature: Send email at user creation
  In order to send an email at user creation
  As a super user
  I need to create a user

  @super-user @user @add-user @1016-1245 @duplicate
  Scenario: 1016-1245 Adding new user
    Given I log in as a user with role "Super User"
    Then I add a new user with "Super User" role
    Then I verify an email was sent
    Then I delete the User
