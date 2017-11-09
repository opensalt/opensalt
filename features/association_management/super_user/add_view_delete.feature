Feature: Adding, viewing and deleting a association management
  In order to confirm the application can add a new association management
  As an super user
  I need to see a all of the fields

  @incomplete @smoke @super-user @association @add-association
  Scenario: 1017-0930 An super-user can add a association group
    Given I log in as a user with role "Super-User"
    Then I create a "Draft" framework
    And I add "Group 1" Manange Assiation Groups

  @incomplete @smoke @super-user @association @viewing-association
  Scenario: 1017-0931 An super-user can see a association group
    Given I log in as a user with role "Super-User"
    And I should see "Group 1" Manange Assiation Groups

  @incomplete @smoke @super-user @association @deleting-association
  Scenario: 1017-0932 An super-user can delete a association group
    Given I log in as a user with role "Super-User"
    Then I delete Manange Assiation Groups for "Group 1" framework
    And I should not see "Group 1" Assiation Groups
    Then I delete "Draft" framework