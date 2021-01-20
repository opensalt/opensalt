Feature: A user should see a captcha after several wrong attempts

  @skip @captcha
  Scenario Outline: An anonymous user lands on the front page
    Given I am logged out
    And a user exists with role "<role>"

    When I am on the homepage
    And I follow "Sign in"
    Then I should see "Username"
    And I should see "Password"
    And I should see "Login"

    When I fill in the username with wrong data
    And I fill in the password with wrong data
    And I press "Login"
    And After several wrong attempts
    Then I should see captcha is rendered

    Examples:
      | role |
      | Super-User |
      | Super-Editor |
      | Admin |
      | Editor |
      | User |
