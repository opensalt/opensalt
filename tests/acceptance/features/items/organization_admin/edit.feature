Feature: Editing a item
  In order to confirm the application can edit item
  As an organization admin
  I need to see a all of the fields

  @organization-admin @item @edit-item @1107-0941 @duplicate
  Scenario: 1107-0941 An organization admin can edit a item
    Given I log in as a user with role "Admin"
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
