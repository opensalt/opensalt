Feature: Create new account

    @public-user @user @add-user
    Scenario: Creating new account
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign in"
        Then I should see "Username"
        And I should see "Password"
        And I should see "Login"
        Then I should see "Create User Account"

        When I follow "Create User Account"
        Then I should see "Create new account"
        Then I create a new account
        Then I should see "Username"
