Feature: Adding, viewing and deleting a exemplars
  In order to confirm the application can add a new exemplars
  As an organization_editor
  I need to see a all of the fields

  @organization-editor @exemplar @add-exemplar @1017-0940
  Scenario: 1017-0940 An organization_editor can add a exemplar
    Given I log in as a user with role "Editor"
    Then I create a framework
    And I add a Item
    And I add "Exemplar" exemplar

    And I should see the exemplar

    Then I delete the exemplar
    And I delete the framework