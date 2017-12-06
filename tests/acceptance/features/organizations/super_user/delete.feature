Feature: Delete Organization
  In order to a delete a Organization
  As an Super-User
  I need to have access to the Organization list page

  @super-user @org @delete-org @1011-1726
  Scenario: 1011-1726 Super-User Deleting a Organization in Organization List
    Given I log in as a user with role "Super-User"
    And  I add a Organization

    Then I delete the Organization