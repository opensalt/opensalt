Feature: Visualize a document with D3 library

  @anonymous @view-actions @0822-1606
  Scenario: 0822-1606 An anonymous user can not see Visualization View button
    Given I am on a framework page
    Then I should not see the visualization view button
