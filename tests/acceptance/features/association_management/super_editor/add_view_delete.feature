Feature: Adding, viewing and deleting a association management
  In order to confirm the application can add a new association management
  As an super editor
  I need to see a all of the fields

  @super-editor @association @add-association @1016-0930
  Scenario: 1016-0930 An editor user can add a association group
    Given I log in as a user with role "Super-Editor"
    Then I create a framework
    And I add a Item

    Then I copy a Item
    And I should see the Item

    Then I add a Association
    And I should see the Association

    Then I delete the Association
    And I should not see the Association

    Then I delete the framework