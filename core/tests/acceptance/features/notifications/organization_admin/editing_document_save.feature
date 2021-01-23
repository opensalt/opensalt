Feature: Change Notifications
When another user finishes editing the document then I see all of the buttons are enabled

  @0108-0804 @change-notification @ui @organization-admin @duplicate
  Scenario: 0108-0804 Notification for document finished editing and when logged in as an Organization Admin
    Given I am logged in as an "Admin"
    And I log a new "Editor"
    When I create a framework
    And I select the document
    And I edit the fields in a framework
      | Title           | New Title           |
      | Creator         | New Creator         |
      | Official URI    | http://opensalt.com |
    Then I see a notification modified "Framework document modified"
    And I see the Document buttons enabled
    And I delete the framework
