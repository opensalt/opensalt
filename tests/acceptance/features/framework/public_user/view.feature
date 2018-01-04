Feature: The framework is viewable by an anonymous user
  In order to confirm the application is up and running
  As an anonymous user
  I need to see a framework

  @smoke @anonymous @view-framework @0901-0001
  Scenario: 0901-0001 An anonymous user can see a framework
    Given I am on a framework page
    Then I should see the framework tree
    And I should see the framework information

    Given I am on an item page
    And I should see the item information
