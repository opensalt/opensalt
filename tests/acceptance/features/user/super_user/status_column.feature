Feature: Status column should be displayed on user page

    @public-user @user @add-user @9922-8928
    Scenario: 9922-8928 Status column should be displayed on user page
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"        
        Then I should see "Create New Account"
      
        Then I create a new account
        Then I should see "Competency Frameworks"

        Then I log in as a user with role "Super-User"
        Then I see last created user account display top of user list page
