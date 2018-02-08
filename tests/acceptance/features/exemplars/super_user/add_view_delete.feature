Feature: Adding, viewing and deleting a exemplars
  In order to confirm the application can add a new exemplars
  As an super-user
  I need to see a all of the fields

  @super-user @exemplar @add-exemplar @1016-0950 @duplicate
  Scenario: 1016-0950 An super-user can add a exemplar
    Given I log in as a user with role "Super-User"
    Then I create a framework
    And I add a Item
    And I add "Exemplar" exemplar

    And I should see the exemplar

    Then I delete the exemplar
    And I delete the framework
