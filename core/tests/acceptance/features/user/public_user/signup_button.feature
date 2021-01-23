Feature: Sign up button in the navbar to get redirected to
    sign up page

    @public-user @user
    Scenario: Clicking sign up button
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"
        Then I should see "Create new account"

    @public-user @user
    Scenario: Seeing organizations message
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"
        Then I should see " If your organization is not in the list, select Other to add it."
