Feature: Reorder a item
  In order to confirm the application can reorder items
  As an super-user
  I need to see reorder section

  @super-user @item @edit-item @1110-1021
  Scenario: 1110-1021 An super-user user can reorder a item
    Given I log in as a user with role "Super-User"
    Then I create a framework
    And I add a Item
    And I add a another Item

    Then I reorder the item
    And I see the item moved

    Then I delete the framework