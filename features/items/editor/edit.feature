Feature: Editing a item
  In order to confirm the application can edit item
  As an editor user
  I need to see a all of the fields

  @incomplete @smoke @editor @item @edit-item
  Scenario Outline: 1016-0945 An editor user can edit a item
    Given I log in as a user with role "Editor"
    Then I create a "Draft" framework
    And I add "ELA" Item
    Then I edit the "<fullStatement>"
    And  I edit the "<notes">
    And I see the updated "<fullStatement>"
    And I see the updated "<notes>"
    Then I delete "Draft" framework
    Examples:
      | fullStatement     | notes     |
      | New Full Statement | New description |
