Feature: Change Log Interaction
  New button <Log View> to view logs

  @incomplete @0117-0702 @logs @ui @organization-admin @duplicate
  Scenario: 0117-0702 Add new item, Update of an item, Move item shows the table of data elements - Logged in as Organization Admin
    Given I am logged in as an "Admin"
    When I create a framework
    And I add the item "First Item"

    And I edit the fields in a item
      | Abbreviated statement | 1st Item |

    And I select the framework node
    And I add the item "Second Item"
    And I reorder the item
    And I select Log View

    Then I should see the add of "First Item" in the log
    And I should see the update of "1st Item" in the log
#    And I should see the move of "Second Item" in the log
    And I delete the framework
