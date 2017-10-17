Feature: Adding, viewing and deleting a association management
  In order to confirm the application can add a new association management
  As an organization admin
  I need to see a all of the fields

  @incomplete @smoke @organization-admin @association @add-association
  Scenario: 1016-1501 An organization-admin can add a association group
    Given I log in as a user with role "Admin"
    Then I create a "Draft" framework
    And I add "Group 1" Manange Assiation Groups

  @incomplete @smoke @organization-admin @association @viewing-association
  Scenario: 1016-1502 An organization-admin can see a association group
    Given I log in as a user with role "Admin"
    And I should see "Group 1" Manange Assiation Groups

  @incomplete @smoke @organization-admin @association @deleting-association
  Scenario: 1016-1503 An organization-admin can delete a association group
    Given I log in as a user with role "Admin"
    Then I delete Manange Assiation Groups for "Group 1" framework
    And I should not see "Group 1" Assiation Groups
    Then I delete "Draft" framework