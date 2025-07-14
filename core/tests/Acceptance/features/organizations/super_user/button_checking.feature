Feature: Back to the list
  In order to see if Back to List button work
  As an Super-User
  I need to have access to the organizations list page

  @super-user @user @1117-1533
  Scenario: 1117-1533 Back to the Users list from the Add an Organization page
    Given I log in as a user with role "Super-User"
    And I am on the Organizations list page
    And I click the "Add a new organization" button
    And I click the "Back to the list" button
    Then I should be on the Organizations list page

  @super-user @user @1117-1535
  Scenario: 1117-1535 Back to the Users list from the Organization edit page
    Given I log in as a user with role "Super-User"
    And I add a Organization

    Then I edit the new Organization
    And I click the "Back to the list" button
    Then I should be on the Organizations list page

  @super-user @user @1117-1543
  Scenario: 1117-1543  Back to the Users list from the Organization page
    Given I log in as a user with role "Super-User"
    And I add a Organization

    Then I show the new Organization
    And I click the "Back to the list" button
    Then I should be on the Organizations list page
