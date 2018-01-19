Feature: Change Log Interaction
  New button <Log View> to view logs

  @0117-0701 @logs @ui @organization-admin
  Scenario: 0117-0701 Log View shows the table of data elements - Logged in as Organization Admin
    Given I am logged in as an "Admin"
    When I create a framework
    Then I see the Log View button in the title section
    When I select Log View
    Then I see the log table
    And I see all of the data table elements
    Then I delete the framework

  @incomplete @0117-0702 @logs @ui @organization-admin
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
