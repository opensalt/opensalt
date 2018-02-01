Feature: Change Notifications
When the Document is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0801 @change-notification @ui @organization-editor
  Scenario: 0108-0801 Notification for document being edited when logged in as an Organization Editor
    Given I am logged in as an "Editor"
    And I log a new "Admin"
    When I create a framework
    And I select the document
    And I edit the fields in a framework without saving the changes
      | Title           | New Title           |
      | Creator         | New Creator         |
      | Official URI    | http://opensalt.com |
    Then I see a notification of editing "Document"
    And I see the Document buttons disabled
    And I delete the framework