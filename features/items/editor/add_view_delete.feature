Feature: Adding, viewing and deleting a item
  In order to confirm the application can add a new item
  As an editor user
  I need to see a all of the fields

  @incomplete @smoke @editor @framework @add-item
  Scenario: 1016-0929 An editor user can add a association group
    Given I log in as a user with role "Editor"
    Then I create a "Draft" framework
    And I add "ELA" Item

  @incomplete @smoke @editor @framework @viewing-item
  Scenario: 1016-0928 An editor user can see a association group
    Given I log in as a user with role "Editor"
    And I should see "ELA" Item

  @incomplete @smoke @editor @framework @deleting-item
  Scenario: 1016-0927 An editor user can delete a association group
    Given I log in as a user with role "Editor"
    Then I delete "ELA" Item
    And I should not see "ELA" Item
    And I delete "Draft" framework