Feature: The framework is editable
  In order to confirm the framework can be edited
  As an super editor
  I need to edit a framework

  @incomplete @smoke @super-editor @view-framework
  Scenario Outline: 1016-1326 An super editor can edit a framework
    Given I log in as a user with role "Super-Editor"
    Then I am on a "Draft" framework page
    And I edit the "<field>" to "<data>"
    And I press "Save Changes"
    Then I should see "<data>" in "<field>"
    And And I delete "Draft" framework
    Examples:
      | field               | data             |
      | Title               | New Title        |
      | Creator             | New Creator      |
      | Official URI        | New URI          |
      | Publisher           | New Publisher    |
      | URL Name            | New URL          |
      | Owning Organization | New Organization |
      | Owning User         | New User         |
      | Version             | New Version      |
      | Description         | New Description  |
      | Subjects            | Math             |
      | Language            | French           |
      | Note                | New Note         |


