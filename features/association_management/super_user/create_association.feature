Feature: Create Association a item
  In order to confirm the application can Create Association item
  As an super-user
  I need to see a all of the item in another framework

  @incomplete @smoke @super-user @item @edit-copy
  Scenario Outline: 1017-1024 An super-user can Create Association item
    Given I log in as a user with role "Super-User"
    Then I create a "Draft" framework
    And I add Association "<assoc>" Items to "<assocNew>"
    And I should see a Association "<assoc>" Items in "<assocNew>"
    Then I delete "Draft" framework
    Examples:
      | assoc   | assocNew     |
      | Grade 1 | Kindergarten |
