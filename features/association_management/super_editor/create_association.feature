Feature: Create Association a item
  In order to confirm the application can Create Association item
  As an super-editor
  I need to see a all of the item in another framework

  @incomplete @smoke @super-editor @item @edit-copy
  Scenario Outline: 1016-1024 An super-editor can Create Association item
    Given I log in as a user with role "Super-Editor"
    Then I create a "Draft" framework
    And I add Association "<assoc>" Items to "<assocNew>"
    And I should see a Association "<assoc>" Items in "<assocNew>"
    Then I delete "Draft" framework
    Examples:
      | assoc   | assocNew     |
      | Grade 1 | Kindergarten |
