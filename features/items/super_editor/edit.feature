Feature: Editing a item
  In order to confirm the application can edit item
  As an super editor
  I need to see a all of the fields

  @super-editor @item @edit-item @1016-0945
  Scenario: 1016-0945 An super editor can edit a item
    Given I log in as a user with role "Super-Editor"
    When I create a framework
    And I add a Item

    Then I edit the fields in a item
      | Human coding scheme   | QA Test Item         |
      | List enum in source   | 1                    |
      | Abbreviated statement | New Abb statement    |
      | Concept keywords      | reading              |
      | Concept keywords uri  | http://reading.com   |
      | Licence uri           | http://somewhere.com |

    Then I should see the Item
    And I delete the Item

    Then I should not see the deleted Item
    And I delete the framework
