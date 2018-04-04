Feature: Adding, viewing and deleting a exemplars
  In order to confirm the application can add a new exemplars
  As an super-editor
  I need to see a all of the fields

  @super-editor @exemplar @add-exemplar @1016-0940 @duplicate
  Scenario: 1016-0940 An super-editor can add a exemplar
    Given I log in as a user with role "Super-Editor"
    Then I create a framework
    And I add a Item
    And I add "Exemplar" exemplar

    And I should see the exemplar

    Then I delete the exemplar
    And I delete the framework
