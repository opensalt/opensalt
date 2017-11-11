Feature: Reorder a item
  In order to confirm the application can reorder items
  As an organization-admin user
  I need to see reorder section

  @organization-admin @item @edit-item @1110-102
  Scenario: 1110-1032 An organization-admin user can reorder a item
    Given I log in as a user with role "Admin"
    Then I create a framework
    And I add a Item
    And I add a another Item

    Then I reorder the item
    And I see the item moved

    Then I delete the framework