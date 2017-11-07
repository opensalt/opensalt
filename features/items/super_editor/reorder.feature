Feature: Reorder a item
  In order to confirm the application can reorder items
  As an editor user
  I need to see reorder section

  @incomplete @smoke @editor @item @edit-item
  Scenario: 1016-1036 An editor user can reorder a item
    Given I log in as a user with role "Editor"
    Then I create a "Draft" framework
    And I reorder "Kindergarten" Item to postion "1"
    And I should see "Kindergarten" Item in postion "1"
    Then I delete "Draft" framework