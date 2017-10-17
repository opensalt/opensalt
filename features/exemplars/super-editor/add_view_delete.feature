Feature: Adding, viewing and deleting a exemplars
  In order to confirm the application can add a new exemplars
  As an super-editor
  I need to see a all of the fields

  @incomplete @smoke @super-editor @exemplar @add-exemplar
  Scenario: 1016-0940 An super-editor can add a exemplar
    Given I log in as a user with role "Super-Editor"
    Then I create a "Draft" framework
    And I add "test" exemplar

  @incomplete @smoke @super-editor @exemplar @viewing-exemplar
  Scenario: 1016-0941 An super-editor can see a exemplars
    Given I log in as a user with role "Super-Editor"
    And I should see "test" exemplar

  @incomplete @smoke @super-editor @exemplar @deleting-exemplar
  Scenario: 1016-0942 An super-editor can delete a exemplars
    Given I log in as a user with role "Super-Editor"
    Then I delete "test" exemplar
    And I delete "Draft" framework