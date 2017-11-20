Feature: Reorder a item
  In order to confirm the application can reorder items
  As an organization-editor user
  I need to see reorder section

  @organization-editor @item @edit-item @1110-1033
  Scenario: 1110-1033 An organization-editor user can reorder a item
    Given I log in as a user with role "Editor"
    Then I create a framework
    And I add a Item
    And I add a another Item

    Then I reorder the item
    And I see the item moved

    Then I delete the framework