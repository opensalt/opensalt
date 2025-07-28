Feature: Deleting a association view
  In order to confirm the application can add a new association view
  As a group admin
  I need to see association view

  @group-admin @association @association-view @1205-1001
  Scenario: 1205-1001 A group-admin can delete a association view
    Given I log in as a user with role "Admin"
    Then I create a framework
    And I add a Item
    And I add an exemplar
    And I add a Association

    Then I delete an exemplar in Association View
    And I should not see an exemplar in Association View

    Then I delete the framework
