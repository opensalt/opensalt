Feature: Search a item
  In order to confirm the application can search item
  As an anonymous
  I need to see the search from

  @incomplete @anonymous @item @search-item @1016-1018
  Scenario: 1016-1018 An anonymous user can edit a item
    Given I log in as a user with role "Editor"
    Then I create a "Draft" framework
    Given I am logged out
    And I search for "RL.K.1" Items
    And I should see "RL.K.1" Items
    When I log in as a user with role "Editor"
    Then I delete "Draft" framework