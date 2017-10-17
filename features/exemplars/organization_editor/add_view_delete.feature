Feature: Adding, viewing and deleting a exemplars
  In order to confirm the application can add a new exemplars
  As an organization_editor
  I need to see a all of the fields

  @incomplete @smoke @organization_editor @exemplar @add-exemplar
  Scenario: 1017-0940 An organization_editor can add a exemplar
    Given I log in as a user with role "Editor"
    Then I create a "Draft" framework
    And I add "test" exemplar

  @incomplete @smoke @organization_editor @exemplar @viewing-exemplar
  Scenario: 1017-0941 An organization_editor can see a exemplars
    Given I log in as a user with role "Editor"
    And I should see "test" exemplar

  @incomplete @smoke @organization_editor @exemplar @deleting-exemplar
  Scenario: 1017-0942 An organization_editor can delete a exemplars
    Given I log in as a user with role "Editor"
    Then I delete "test" exemplar
    And I delete "Draft" framework