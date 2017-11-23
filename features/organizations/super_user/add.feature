Feature: Add new Organization
  In order to a add a Organization
  As an Super-User
  I need to have access to the Organizations list page

  @super-user @org @add-org @1011-1715
  Scenario: 1011-1715 Super-User adding new Organization
    Given I log in as a user with role "Super-User"
    Then  I add a Organization

    Then I delete the Organization