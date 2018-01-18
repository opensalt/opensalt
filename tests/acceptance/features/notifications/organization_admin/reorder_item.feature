Feature: Change Notifications
When a user moves an item, Item Move Notifications are displayed

  @0108-0826 @change-notification @ui @organization-admin
  Scenario: 0108-0826 Item Move Notifications when logged in as an Organization Admin
    Given I am logged in as an "Admin"
    And I log a new "Editor"
    When I create a framework
    And I select the document
    And I add a Item
    And I add a another Item
    And I reorder the item
    Then I see a notification modified "Framework tree updated"
    And Show link is selected from the notification
    Then that item is selected
    And I delete the framework