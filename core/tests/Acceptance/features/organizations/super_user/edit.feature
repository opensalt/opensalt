Feature: Edit an existing Group
  In order to a edit a Group
  As an Super-User
  I need to have access to the Groups List page

  @super-user @group @edit-group @1011-1728
  Scenario: 1011-1728 Super-User Editing an Group in Groups List
    Given I log in as a user with role "Super-User"
    Then  I add a Group

    Then I edit the name in Group
      | A new org |

    And I delete the Group
