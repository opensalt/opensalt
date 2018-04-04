Feature: Back to the list
  In order to see if Back to List button work
  As an organization admin
  I need to have access to the user profile page

  @organization-admin @user @1117-1333
  Scenario: 1117-1333 Back to the Users list from the Add a User page
    Given I log in as a user with role "Admin"
    And I am on the User list page
    And I click the "Add a new user" button
    And I click the "Back to the list" button
    Then I am on the User list page

  @organization-admin @user @1117-1335
  Scenario: 1117-1335 Back to the Users list from the User edit page
    Given I log in as a user with role "Admin"
    And I add a new user
    And I approve the new user

    Then I edit the new user
    And I click the "Back to the list" button
    Then I am on the User list page

  @organization-admin @user @1117-1343
  Scenario: 1117-1343  Back to the Users list from the User page
    Given I log in as a user with role "Admin"
    And I add a new user

    Then I show the new user
    And I click the "Back to the list" button
    Then I am on the User list page
