Feature: Adding, viewing and deleting a association management
  In order to confirm the application can add a new association management
  As an editor user
  I need to see a all of the fields

  @incomplete @smoke @editor @association @add-association
  Scenario: 1016-0930 An editor user can add a association group
    Given I log in as a user with role "Editor"
    Then I create a "Draft" framework
    And I add "Group 1" Manange Assiation Groups

  @incomplete @smoke @editor @association @viewing-association
  Scenario: 1016-0931 An editor user can see a association group
    Given I log in as a user with role "Editor"
    And I should see "Group 1" Manange Assiation Groups

  @incomplete @smoke @editor @association @deleting-association
  Scenario: 1016-0932 An editor user can delete a association group
    Given I log in as a user with role "Editor"
    Then I delete Manange Assiation Groups for "Group 1" framework
    And I should not see "Group 1" Assiation Groups
    Then I delete "Draft" framework