Feature: View Organization
  In order to an organization
  As an super-user
  I need to have access to the Organization profile page

  @super-user @org @view-org @1011-1737
  Scenario: 1011-1737 Super-User Viewing organization profile page
    Given I log in as a user with role "Super-User"
    Then  I add a Organization

    Then I should see the Organization
    And I delete the Organization