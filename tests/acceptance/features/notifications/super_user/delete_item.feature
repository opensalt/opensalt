Feature: Change Notifications
When a user deletes an item, Item Delete Notifications are displayed

  @0108-0821 @change-notification @ui @super-user @duplicate
  Scenario: 0108-0821 Item Delete Notifications when logged in as an Super-User
    Given I am logged in as an "Super-User"
    And I log a new "Super-Editor"
    When I create a framework
    And I add a Item
    And I select the item
    When I delete the Item
    Then I see a notification modified "and children deleted"
    And I should not see the deleted Item
    And I delete the framework
