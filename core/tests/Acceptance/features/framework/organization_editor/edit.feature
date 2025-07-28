Feature: The framework is editable
  In order to confirm the framework can be edited
  As an group-editor
  I need to edit a framework

  @group-editor @view-framework @1013-1444
  Scenario: 1013-1444 A group editor can edit a framework
    Given I log in as a user with role "Editor"
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
