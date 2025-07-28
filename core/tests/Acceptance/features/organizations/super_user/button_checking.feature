Feature: Back to the list
  In order to see if Back to List button work
  As an Super-User
  I need to have access to the groups list page

  @super-user @user @1117-1533
  Scenario: 1117-1533 Back to the Users list from the Add a Group page
    Given I log in as a user with role "Super-User"
    And I am on the Groups list page
    And I click the "Add a new access group" button
    And I click the "Back to the list" button
    Then I should be on the Groups list page

  @super-user @user @1117-1535
  Scenario: 1117-1535 Back to the Users list from the Group edit page
    Given I log in as a user with role "Super-User"
    And I add a Group

    Then I edit the new Group
    And I click the "Back to the list" button
    Then I should be on the Groups list page

  @super-user @user @1117-1543
  Scenario: 1117-1543  Back to the Users list from the Group page
    Given I log in as a user with role "Super-User"
    And I add a Group

    Then I show the new Group
    And I click the "Back to the list" button
    Then I should be on the Groups list page
