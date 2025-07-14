Feature: The framework is editable
  In order to confirm the framework can be edited
  As an Super-User
  I need to edit a framework

  @super-user @view-framework @1016-1326 @duplicate
  Scenario: 1016-1326 An Super-User can edit a framework
    Given I log in as a user with role "Super-User"
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

  Scenario: 1016-1326 An Super-User can see licence drop-down
    Given I log in as a user with role "Super-User"
    When I display modal to edit framework
    Then I should see licence edit drop-down
