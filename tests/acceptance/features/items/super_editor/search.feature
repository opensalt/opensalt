Feature: Search a item
  In order to confirm the application can copy item
  As an super editor user
  I need to see the search from

  @super-editor @item @search-item @1016-1018 @duplicate
  Scenario: 1016-1018 An super editor user can edit a item
    Given I log in as a user with role "Super-Editor"
    And I create a framework
    And I add "3" Items
    And I add "Something" Item

    Then I search for "Something" in the framework
    And I should not see "Test Item 1" in results
    And I should see "Something"

    Then I delete the framework