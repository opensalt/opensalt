Feature: Adding, viewing and deleting a exemplars
  In order to confirm the application can add a new exemplars
  As an super-user
  I need to see a all of the fields

  @incomplete @smoke @super-user @exemplar @add-exemplar
  Scenario: 1016-0950 An super-user can add a exemplar
    Given I log in as a user with role "Super-User"
    Then I create a "Draft" framework
    And I add "test" exemplar

  @incomplete @smoke @super-user @exemplar @viewing-exemplar
  Scenario: 1016-0951 An super-user can see a exemplars
    Given I log in as a user with role "Super-User"
    And I should see "test" exemplar

  @incomplete @smoke @super-user @exemplar @deleting-exemplar
  Scenario: 1016-0952 An super-user can delete a exemplars
    Given I log in as a user with role "Super-User"
    Then I delete "test" exemplar
    And I delete "Draft" framework