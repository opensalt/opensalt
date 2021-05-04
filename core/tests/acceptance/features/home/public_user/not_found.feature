Feature: An anonymous user can go to a non-existent page
  As an anonymous user
  If I go to a non-existent page
  I should see the "Not Found" page

  @smoke @anonymous @0206-0832
  Scenario: 0206-0832 An anonymous user lands on a non-existent page
    Given I am on the page "/not-existent-page"
    Then I should see "Page not found"
