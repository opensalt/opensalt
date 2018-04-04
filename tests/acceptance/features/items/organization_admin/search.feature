Feature: Search a item
  In order to confirm the application can copy item
  As an organization-admin
  I need to see the search from

  @organization-admin @item @search-item @1016-1024 @duplicate
  Scenario: 1016-1024 An organization-admin can edit a item
    Given I log in as a user with role "Admin"
    And I create a framework
    And I add "14" Items
    And I add "Something" Item

    Then I search for "Something" in the framework
    And I should not see "Test Item 1" in results
    And I should see "Something"

    Then I delete the framework