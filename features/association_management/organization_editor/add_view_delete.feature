Feature: Adding, viewing and deleting a association management
  In order to confirm the application can add a new association management
  As an organization-editor
  I need to see a all of the fields

  @incomplete @smoke @organization-editor @association @add-association
  Scenario: 1016-1505 An organization-editor can add a association group
    Given I log in as a user with role "Editor"
    Then I create a "Draft" framework
    And I add "Group 1" Manange Assiation Groups

  @incomplete @smoke @organization-editor @association @viewing-association
  Scenario: 1016-1506 An organization-editor can see a association group
    Given I log in as a user with role "Editor"
    And I should see "Group 1" Manange Assiation Groups

  @incomplete @smoke @organization-editor @association @deleting-association
  Scenario: 1016-0932 An organization-editor can delete a association group
    Given I log in as a user with role "Editor"
    Then I delete Manange Assiation Groups for "Group 1" framework
    And I should not see "Group 1" Assiation Groups
    Then I delete "Draft" framework