Feature: Change Log Interaction
  Framework that has at least 25 changes

  @0117-0708 @logs @ui @organization-editor
  Scenario: 0117-0708 User should see 25 rows in the data table when 25 selected from the Show Entries selected - Logged in as Organization Editor
    Given I am logged in as an "Editor"
    #And a <frameworkName> exists that has at least 25 changes
    When I create a framework
    And I add the item "First Item"
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 1 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 2 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 3 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 4 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 5 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 6 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 7 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 8 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 9 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 10 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 11 |
    And I edit the fields in a item
      | Abbreviated statement | 1st Item 12 |

    And I select the framework node

    And I add the item "2nd Item"
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 1 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 2 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 3 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 4 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 5 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 6 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 7 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 8 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 9 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 10 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 11 |
    And I edit the fields in a item
      | Abbreviated statement | 2nd Item 12 |
    And I select Log View
    And I select 25 from the "Show entries" dropdown
    Then I should see 25 rows in the data table
    And I delete the framework
