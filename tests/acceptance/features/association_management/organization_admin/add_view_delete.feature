Feature: Adding, viewing and deleting a association management
  In order to confirm the application can add a new association management
  As an organization admin
  I need to see a all of the fields

  @organization-admin @association @add-association @1016-1501 @duplicate
  Scenario: 1016-1501 An organization-admin can add a association group
    Given I log in as a user with role "Admin"
    Then I create a framework
    And I add a Item

    Then I copy a Item
    And I should see the Item

    Then I add a Association
    And I should see the Association

    Then I delete the Association
    And I should not see the Association

    Then I delete the framework
