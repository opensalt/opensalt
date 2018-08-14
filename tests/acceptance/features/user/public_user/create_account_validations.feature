Feature: Create new account validations

    @public-user @user @add-user
    Scenario: Must be a valid email
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"

        Then I should see "Create new account"
        Then I fill in the "Username (Email address)" with "username"
        Then I fill in the "Password" with "Password123!"
        Then I fill in the "Confirm Password" with "Password123!"
        Then I select option "3" from "Organization"
        Then I click "Submit"
        Then I should see "This value is not a valid email address."

    @public-user @user @add-user
    Scenario: Must match passwords
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"

        Then I should see "Create new account"
        Then I fill in the "Username (Email address)" with "user@opensalt.com"
        Then I fill in the "Password" with "Password123!"
        Then I fill in the "Confirm Password" with "password"
        Then I select option "3" from "Organization"
        Then I click "Submit"
        Then I should see "Passwords do not match"

    @public-user @user @add-user
    Scenario: Must be a valid password
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"

        Then I should see "Create new account"
        Then I fill in the "Username (Email address)" with "user@opensalt.com"
        Then I fill in the "Password" with "password"
        Then I fill in the "Confirm Password" with "password"
        Then I select option "3" from "Organization"
        Then I click "Submit"
        Then I should see "Password does not match required criteria"
