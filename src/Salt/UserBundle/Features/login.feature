Feature: A user can sign into the application
  In order to manage frameworks
  As an anonymous user
  I need to sign in and become an authenticated user

  @javascript
  Scenario: An anonymous user lands on the front page
    Given I am on the homepage
    Then I should see "Competency Frameworks"
    And I should see "Sign in" in the "a.login" element

    When I follow "Sign in"
    Then I should see "Email Address"
    And I should see "Password"
    And I should see "Login"

    Given the user "behatadmin" exists with role "Super-User"
    When I fill in "Email Address" with the username
    And I fill in "Password" with the password
    And I press "Login"
    Then I should see "Signed in as"
