Feature: Click Approve Button Hide Approve And display suspend button 
  In order to a edit a user
  As an organization admin
  I need to have access to the user profile page

  @admin @user @reinstate @0720-0007
  Scenario: 0720-0007 Click Approve Button Hide Approve And display suspend button
    Given I log in as a user with role "Admin"
    And I add a new user
    And I approve the new user
    And I suspend the user
    And I reinstate the user
    
    
    And I delete the User