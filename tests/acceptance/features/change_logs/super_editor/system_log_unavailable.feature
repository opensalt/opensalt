Feature: Change Log Interaction
  System Audit Log

  @0117-0734 @logs @ui @super-editor
  Scenario: 0117-0734 User cannot view the system history log- Login as a Super Editor or an Org Admin or an Org Editor
    Given I am logged in as an "Super-Editor"
    Then I do not see "Manage system logs" in the menu dropdown
