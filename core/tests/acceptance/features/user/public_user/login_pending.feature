Feature: A pending user can not sign into the application

  @login
  Scenario Outline: An anonymous user lands on the front page
    Given I am logged out
    And a pending user exists with role "<role>"

    When I am on the homepage
    And I follow "Sign in"
    Then I should see "Username"
    And I should see "Password"
    And I should see "Login"

    When I fill in the username
    And I fill in the password
    And I press "Login"
    Then I should see "Account is locked"

    Examples:
      | role |
      | User |
