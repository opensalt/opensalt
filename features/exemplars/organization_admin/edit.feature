Feature: Editing a exemplar
  In order to confirm the application can edit exemplar
  As an organization_admin
  I need to see a all of the fields

  @smoke @organization_admin @exemplar @edit-exemplar
  Scenario Outline: 1017-0948 An organization_admin can edit a exemplar
    Given I log in as a user with role "Admin"
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
