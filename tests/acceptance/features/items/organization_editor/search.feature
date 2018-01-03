Feature: Search a item
  In order to confirm the application can copy item
  As an organization-editor
  I need to see the search from

  @organization-editor @item @search-item @1016-1023
  Scenario: 1016-1023 An organization-editor can edit a item
    Given I log in as a user with role "Editor"
    And I create a framework
    And I add "5" Items
    And I add "Something" Item

    Then I search for "Something" in the framework
    And I should not see "Test Item 1" in results
    And I should see "Something"

    Then I delete the framework