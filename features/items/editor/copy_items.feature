Feature: Coping a item
  In order to confirm the application can copy item
  As an editor user
  I need to see a all of the item in another framework

  @incomplete @smoke @editor @item @copy-item
  Scenario: 1016-0926 An editor user can edit a item
    Given I log in as a user with role "Editor"
    Then I create a "Draft" framework
    And I copy "Grade 1" Items
    And I should see a copy "Grade 1" Items
    Then I delete "Draft" framework

