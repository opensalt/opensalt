Feature: Adding, viewing and deleting a exemplars
  In order to confirm the application can add a new exemplars
  As an editor user
  I need to see a all of the fields

  @incomplete @smoke @editor @framework @add-exemplar
  Scenario: 1016-0940 An editor user can add a exemplar
    Given I log in as a user with role "Editor"
    Then I create a "Draft" framework
    And I add "test" exemplar

  @incomplete @smoke @editor @framework @viewing-exemplar
  Scenario: 1016-0941 An editor user can see a exemplars
    Given I log in as a user with role "Editor"
    And I should see "test" exemplar

  @incomplete @smoke @editor @framework @deleting-exemplar
  Scenario: 1016-0942 An editor user can delete a exemplars
    Given I log in as a user with role "Editor"
    Then I delete "test" exemplar
    And I delete "Draft" framework