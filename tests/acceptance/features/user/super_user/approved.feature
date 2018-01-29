Feature: Approved an registered User
  In order to a approved a user
  As an super-user
  I need to have access to the user profile page

  @super-user @user @approved @1016-1556 @duplicate
  Scenario: 1016-1556 Approved a user in User List
    Given I log in as a user with role "Super User"
    Then I approved the user
    

    
