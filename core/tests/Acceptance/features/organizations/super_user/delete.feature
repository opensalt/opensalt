Feature: Delete Group
  In order to a delete a Group
  As an Super-User
  I need to have access to the Group list page

  @super-user @group @delete-group @1011-1726
  Scenario: 1011-1726 Super-User Deleting a Group in Group List
    Given I log in as a user with role "Super-User"
    And  I add a Group

    Then I delete the Group
