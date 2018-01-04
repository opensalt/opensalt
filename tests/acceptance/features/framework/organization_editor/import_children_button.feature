Feature: Adding, viewing and deleting a framework
  In order to confirm the application can add a new framework
  As an organization editor
  I need to see a all of the fields

  @organization-editor @framework @ui @1201-1207
  Scenario: 1201-1207 An organization editor can add a framework
    Given I log in as a user with role "Editor"
    Then I should see "Create a new Framework" button

    When I click the "Create a new Framework" button
    Then I should see "LsDoc creation"

    When I create a "Draft" framework
    Then I should see the framework

     And I should see the button "Export"
     And I should see the button "Edit"
     And I should see the button "Manage Association Groups"
     And I should see the button "Add New Child Item"
     And I should see the button "Import Children"
     And I should see the button "Update Framework"

    And I delete the framework
