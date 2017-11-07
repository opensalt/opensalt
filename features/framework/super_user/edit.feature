Feature: The framework is editable
  In order to confirm the framework can be edited
  As an Super-User
  I need to edit a framework

  @incomplete @smoke @super-user @view-framework @123
  Scenario: 1016-1326 An Super-User can edit a framework
    Given I log in as a user with role "Super-User"
    When I create a framework
    And I edit the fields
      | Title           | New Title           |
      | Creator         | New Creator         |
      | Official URI    | http://opensalt.com |
      | Publisher       | New Publisher       |
      | Version         | 2.0                 |
      | Description     | New Description     |
      | Adoption Status | Private Draft       |
      | Language        | French              |
      | Note            | New Note            |
    And I press "Save Changes"

    Then I should see the framework data
    And I delete the framework




