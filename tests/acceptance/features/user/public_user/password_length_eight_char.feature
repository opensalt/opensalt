Feature: Create new user account password should be 8 characters long

    @public-user @user @add-user @5566-7620
    Scenario: 5566-7620 Must match passwords
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

    @public-user @user @add-user @5567-7621
    Scenario: 5567-7621 Must be a valid password
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

    @public-user @user @add-user @5568-7622
    Scenario: 5569-7622 Password must be 8 characters long
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"

        Then I should see "Create new account"
        Then I fill in the "Username (Email address)" with "user@opensalt.com"
        Then I fill in the "Password" with "pass@1"
        Then I fill in the "Confirm Password" with "pass@1"
        Then I select option "3" from "Organization"
        Then I click "Submit"
        Then I should see "Password must be at least 8 characters long"
