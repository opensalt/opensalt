Feature: Change Notifications
When a user creates an item, Item Create Notifications are displayed

  @0108-0825 @change-notification @ui @super-user @duplicate
  Scenario: 0108-0825 Item Create Notifications when logged in as an Super-User
    Given I am logged in as an "Super-User"
    And I log a new "Super-Editor"
    When I create a framework
    And I add a Item
    Then I see a notification modified "added as a child of"
    And Show link is selected from the notification
    Then that item is selected
    And I delete the framework
