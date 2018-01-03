Feature: An anonymous user can view the home page
  In order to confirm the application is up and running
  As an anonymous user
  I need to see a homepage

  @smoke @anonymous @0901-0005
  Scenario: 0901-0005 An anonymous user lands on the front page
    Given I am on the homepage
    Then I should see "Competency Frameworks"
    And I should see "About OpenSALT"
    And I should see "Sign in" in the header

    When I follow "About OpenSALT"
    Then I should see "Open Source"
    And I should see "Sign in" in the header

    When I follow "Sign in"
    Then I should see "Username"
    And I should see "Password"
    And I should see "Login"
