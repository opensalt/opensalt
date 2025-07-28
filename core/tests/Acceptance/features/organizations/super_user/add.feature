Feature: Add new Group
  In order to a add a Group
  As an Super-User
  I need to have access to the Groups list page

  @super-user @group @add-group @1011-1715
  Scenario: 1011-1715 Super-User adding new Group
    Given I log in as a user with role "Super-User"
    Then  I add a Group

    Then I delete the Group
