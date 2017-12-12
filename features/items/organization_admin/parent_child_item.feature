Feature: Child Item
  In order to confirm the application can make a child item
  As an admin user
  I need to have items in framework

  @item @parent-child-item @1117-1527
  Scenario: 1117-1527 An admin user can make a child item to a parent item and a parent item to a child item
    Given I log in as a user with role "Admin"
    When I create a framework
    And I add "2" Items
    And I move the last item to a Parent Item

    Then I see the Child item of the Parent
    And I delete the framework
