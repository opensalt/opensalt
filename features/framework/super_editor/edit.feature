Feature: The framework is editable
  In order to confirm the framework can be edited
  As an super editor
  I need to edit a framework

  @smoke @super-editor @view-framework @1116-1326
  Scenario: 1116-1326 An super editor can edit a framework
    Given I log in as a user with role "Super-Editor"
    When I create a framework
    And I edit the fields in a framework
      | Title           | New Title           |
      | Creator         | New Creator         |
      | Official URI    | http://opensalt.com |
      | Publisher       | New Publisher       |
      | Version         | 2.0                 |
      | Description     | New Description     |
      | Adoption Status | Deprecated          |
      | Language        | fr                  |
      | Note            | New Note            |

    Then I should see the framework data
    And I delete the framework



