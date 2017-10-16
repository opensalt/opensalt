Feature: Editing a exemplar
  In order to confirm the application can edit exemplar
  As an editor user
  I need to see a all of the fields

  @smoke @editor @framework @edit-exemplar
  Scenario Outline: 1016-0944 An editor user can edit a exemplar
    Given I log in as a user with role "Editor"
    Then I create a "Draft" framework
    And I add "test" exemplar
    Then I edit the "<exemplarUrl>"
    And  I edit the "<description">
    And I see the updated "<exemplarUrl>"
    And I see the updated "<description>"
    Then I delete "Draft" framework
    Examples:
      | exemplarUrl     | description     |
      | http://test.com | New description |
