Feature: Search a item
  In order to confirm the application can copy item
  As an super user
  I need to see the search from

  @super-user @item @search-item @1016-1022
  Scenario: 1016-1022 An super user can edit a item
    Given I log in as a user with role "Super-User"
    And I create a framework
    And I add "7" Items
    And I add "Something" Item

    Then I search for "Something" in the framework
    And I should not see "Test Item 1" in results
    And I should see "Something"

    Then I delete the framework