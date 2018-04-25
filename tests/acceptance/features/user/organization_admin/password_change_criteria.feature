Feature: Password change validations
    In order to change my password
    As an organization admin
    I need to put in a new password

    @admin @user @password @8923-8924
    Scenario: Must match passwords
        Given I log in as a user with role "Admin"

        Then I fill in the old password
        Then I fill in the "New Password" with "opensalt"
        Then I fill in the "Repeat Password" with "opensal"
        Then I click "Change Password"
        Then I should see "The password fields must match" 

    @admin @user @password @8929-8930 
    Scenario: Must be 8 char long  passwords
        Given I log in as a user with role "Admin"

        Then I fill in the old password
        Then I fill in the "New Password" with "open"
        Then I fill in the "Repeat Password" with "open"
        Then I click "Change Password"
        Then I should see "Password must be at least 8 characters long"
        Then I should see "Password does not match required criteria"

    @admin @user @password @1011-0905
    Scenario: 1011-0905 Changing my Password
      Given I log in as a user with role "Admin"
      Then I change my password
