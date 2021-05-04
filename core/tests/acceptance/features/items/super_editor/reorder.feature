Feature: Reorder a item
  In order to confirm the application can reorder items
  As an super-editor user
  I need to see reorder section

  @super-editor @item @edit-item @1110-1036 @duplicate @skip-firefox
  Scenario: 1110-1036 An editor user can reorder a item
    Given I log in as a user with role "Super-Editor"
    Then I create a framework
    And I add a Item
    And I add a another Item

    Then I reorder the item
    And I see the item moved

    Then I delete the framework