Feature: Adding, viewing and deleting a association management
  In order to confirm the application can add a new association management
  As an group-editor
  I need to see a all of the fields

  @group-editor @association @add-association @1109-1505
  Scenario: 1109-1505 A group-editor can add a association group
    Given I log in as a user with role "Editor"
    Then I create a framework
    And I add a Item

    Then I copy a Item
    And I should see the Item

    Then I add a Association
    And I should see the Association

    Then I delete the Association
    And I should not see the Association

    Then I delete the framework
