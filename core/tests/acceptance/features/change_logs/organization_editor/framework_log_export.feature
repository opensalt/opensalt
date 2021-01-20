Feature: Change Log Interaction
  Framework Log Export

  @0117-0726 @logs @ui @organization-editor
  Scenario: 0117-0726 User can have a copy in csv with all of the history data - Logged in as Org Editor
    Given I am logged in as an "Editor"
    #And a <frameworkName> exists that has history
    When I create a framework
    And I add the item "First Item"

    And I edit the fields in a item
      | Abbreviated statement | 1st Item |

    And I select the framework node
    And I add the item "Second Item"
    And I reorder the item
    And I select Log View
    And I download the framework revision export CSV
    Then I can see the data in the CSV matches the data in the framework log
    And I delete the framework
