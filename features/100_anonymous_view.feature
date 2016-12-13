Feature: An anonymous user can view the home page
  Scenario: An anonymous user lands on the front page
    Given I am on the homepage
    Then I should see "Competency Frameworks"
    And I should see "Login" in the "a.login" element
