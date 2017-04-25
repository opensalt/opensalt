Feature: A user can sign into the application
  In order to manage frameworks
  As an anonymous user
  I need to sign in and become an authenticated user

  @javascript
  Scenario Outline: An anonymous user lands on the front page
    Given I am on the homepage
    When I follow "Sign in"
    Then I should see "Email Address"
    And I should see "Password"
    And I should see "Login"

    Given a user exists with role "<role>"
    When I fill in "Email Address" with the username
    And I fill in "Password" with the password
    And I press "Login"
    Then I should see "Signed in as"
    Examples:
      | role |
      | Super-User |
      | Super-Editor |
      | Admin |
      | Editor |
