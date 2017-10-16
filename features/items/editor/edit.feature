Feature: Editing a item
  In order to confirm the application can edit item
  As an editor user
  I need to see a all of the fields

  @incomplete @smoke @editor @framework @edit-item
  Scenario Outline: 1016-0926 An editor user can edit a association group
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
