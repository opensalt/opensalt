Feature: Visualize a document with D3 library

  @super-user @framework @view-action @0822-1625
  Scenario: 0822-1625 As Admin can see Visualization View button
    Given I log in as a user with role "Admin"
    Given I am on a framework page
    Then I should not see the visualization view button

