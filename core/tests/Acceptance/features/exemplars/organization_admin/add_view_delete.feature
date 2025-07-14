Feature: Adding, viewing and deleting a exemplars
  In order to confirm the application can add a new exemplars
  As an organization_admin
  I need to see a all of the fields

  @smoke @organization-admin @exemplar @add-exemplar @1017-0945 @duplicate
  Scenario: 1017-0945 An organization_admin can add a exemplar
    Given I log in as a user with role "Admin"
    Then I create a framework
    And I add a Item
    And I add "Exemplar" exemplar

    And I should see the exemplar

    Then I delete the exemplar
    And I delete the framework
