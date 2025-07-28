Feature: View Group
  In order to an group
  As an super-user
  I need to have access to the Group profile page

  @super-user @group @view-group @1011-1737
  Scenario: 1011-1737 Super-User Viewing group profile page
    Given I log in as a user with role "Super-User"
    Then  I add a Group

    Then I should see the Group
    And I delete the Group
