Feature: Change Notifications
When another user finishes editing the document then I see all of the buttons are enabled

  @0108-0804 @change-notification @ui @super-editor @duplicate
  Scenario: 0108-0804 Notification for document finished editing and when logged in as an Super-Editor
    Given I am logged in as an "Super-Editor"
    And I log a new "Super-User"
    When I create a framework
    And I select the document
    And I edit the fields in a framework
      | Title           | New Title           |
      | Creator         | New Creator         |
      | Official URI    | http://opensalt.com |
    Then I see a notification modified "Framework document modified"
    And I see the Document buttons enabled
    And I delete the framework
