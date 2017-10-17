Feature: Adding, viewing and deleting a exemplars
  In order to confirm the application can add a new exemplars
  As an organization_admin
  I need to see a all of the fields

  @incomplete @smoke @organization-admin @exemplar @add-exemplar
  Scenario: 1017-0945 An organization_admin can add a exemplar
    Given I log in as a user with role "Admin"
    Then I create a "Draft" framework
    And I add "test" exemplar

  @incomplete @smoke @organization_admin @exemplar @viewing-exemplar
  Scenario: 1017-0946 An organization_admin can see a exemplars
    Given I log in as a user with role "Admin"
    And I should see "test" exemplar

  @incomplete @smoke @organization_admin @exemplar @deleting-exemplar
  Scenario: 1017-0947 An organization_admin can delete a exemplars
    Given I log in as a user with role "Admin"
    Then I delete "test" exemplar
    And I delete "Draft" framework