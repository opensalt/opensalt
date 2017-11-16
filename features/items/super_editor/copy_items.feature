Feature: Coping a item
  In order to confirm the application can copy item
  As an super editor
  I need to see a all of the item in another framework

  @super-editor @item @copy-item @1016-0926
  Scenario: 1016-0926 An super-editor user can edit a item
    Given I log in as a user with role "Super-Editor"
    Then I create a framework
    And I add a Item

    Then I copy a Item
    And I should see the Item
    Then I delete the framework

