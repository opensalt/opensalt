Feature: Edit an existing Organization
  In order to a edit a Organization
  As an Super-User
  I need to have access to the Organizations List page

  @super-user @org @edit-org @1011-1728
  Scenario: 1011-1728 Super-User Editing an Organization in Organizations List
    Given I log in as a user with role "Super-User"
    Then  I add a Organization

    Then I edit the name in Organization
      | A new org |

    And I delete the Organization