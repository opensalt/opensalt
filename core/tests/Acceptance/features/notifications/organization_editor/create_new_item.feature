Feature: Change Notifications
When a user creates an item, Item Create Notifications are displayed

  @0108-0823 @change-notification @ui @organization-editor
  Scenario: 0108-0823 Item Create Notifications when logged in as an Organization Editor
    Given I am logged in as an "Editor"
    And I log a new "Admin"
    When I create a framework
    And I add a Item
    Then I see a notification modified "added as a child of"
    And Show link is selected from the notification
    Then that item is selected
    And I delete the framework