Feature: Pending new user should be display on top of list

    @public-user @user @add-user
    Scenario: Pending new user should be display on top of list
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"        
        Then I should see "Create User Account"

        When I follow "Create User Account"
        Then I should see "Create new account"
        Then I create a new account
        Then I should see "Competency Frameworks"

        Then I log in as a user with role "Super-User"
        Then I see last created user account display top of user list page
