Feature: Visualize a document with D3 library

  @super-user @framework @view-action @0822-1625
  Scenario: 0822-1625 As Super-Editor can see Visualization View button
    Given "visualization_feature" is enabled
    Given I log in as a user with role "Super-Editor"
    Given I am on a framework page
    Then I should see the visualization view button

