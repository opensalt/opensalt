Feature: Change Notifications
  When the Document is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0800 @change-notification @ui @organization-admin
  Scenario: 0108-0800 Notification for document being edited when logged in as an Organization Admin
    Given I am logged in as an "Admin"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the document
    And another user is editing the document
    Then I see a notification of editing document
    And I see the all of the buttons are disabled

Feature: Change Notifications
  When the Document is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0801 @change-notification @ui @organization-editor
  Scenario: 0108-0801 Notification for document being edited when logged in as an Organization Editor
    Given I am logged in as an "Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the document
    And another user is editing the document
    Then I see a notification of editing document
    And I see the all of the buttons are disabled

Feature: Change Notifications
  When the Document is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0802 @change-notification @ui @super-user
  Scenario: 0108-0802 Notification for document being edited when logged in as an Super User
    Given I am logged in as an "Super-User"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the document
    And another user is editing the document
    Then I see a notification of editing document
    And I see the all of the buttons are disabled

Feature: Change Notifications
  When the Document is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0803 @change-notification @ui @super-editor
  Scenario: 0108-0803 Notification for document being edited when logged in as an Super Editor
    Given I am logged in as an "Super-Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the document
    And another user is editing the document
    Then I see a notification of editing document
    And I see the all of the buttons are disabled

Feature: Change Notifications
  When another user finishes editing the document then I see all of the buttons are enabled

  @0108-0804 @change-notification @ui @organization-admin
  Scenario: 0108-0804 Notification for document finished editing and when logged in as an Orgnaization Admin
    Given I am logged in as an "Admin"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the document
    And another user finishes editing the document
    Then I see a notification of modified document
    And I see the all of the buttons are enabled

Feature: Change Notifications
  When another user finishes editing the document then I see all of the buttons are enabled

  @0108-0805 @change-notification @ui @organization-editor
  Scenario: 0108-0805 Notification for document finished editing and when logged in as an Orgnaization Editor
    Given I am logged in as an "Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the document
    And another user finishes editing the document
    Then I see a notification of modified document
    And I see the all of the buttons are enabled

Feature: Change Notifications
  When another user finishes editing the document then I see all of the buttons are enabled

  @0108-0806 @change-notification @ui @super-user
  Scenario: 0108-0806 Notification for document finished editing and when logged in as an Super User
    Given I am logged in as an "Super-User"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the document
    And another user finishes editing the document
    Then I see a notification of modified document
    And I see the all of the buttons are enabled

Feature: Change Notifications
  When another user finishes editing the document then I see all of the buttons are enabled

  @0108-0807 @change-notification @ui @super-editor
  Scenario: 0108-0807 Notification for document finished editing and when logged in as an Super Editor
    Given I am logged in as an "Super-Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the document
    And another user finishes editing the document
    Then I see a notification of modified document
    And I see the all of the buttons are enabled

Feature: Change Notifications
  When an item is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0808 @change-notification @ui @organization-admin
  Scenario: 0108-0808 Notification for item being edited when logged in as an Organization Admin
    Given I am logged in as an "Admin"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the item <item>
    And another user is editing the item <item>
    Then I see a notification of editing item
    And I see the all of the buttons are disabled
    And the item is marked in the tree
    When Show link selected from the notification
    Then that item is selected

Feature: Change Notifications
  When an item is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0809 @change-notification @ui @organization-editor
  Scenario: 0108-0809 Notification for item being edited when logged in as an Organization Editor
    Given I am logged in as an "Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the item <item>
    And another user is editing the item <item>
    Then I see a notification of editing item
    And I see the all of the buttons are disabled
    And the item is marked in the tree
    When Show link selected from the notification
    Then that item is selected

Feature: Change Notifications
  When an item is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0810 @change-notification @ui @super-user
  Scenario: 0108-0810 Notification for item being edited when logged in as an Super User
    Given I am logged in as an "Super-User"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the item <item>
    And another user is editing the item <item>
    Then I see a notification of editing item
    And I see the all of the buttons are disabled
    And the item is marked in the tree
    When Show link selected from the notification
    Then that item is selected

Feature: Change Notifications
  When an item is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0811 @change-notification @ui @super-editor
  Scenario: 0108-0811 Notification for item being edited when logged in as an Super Editor
    Given I am logged in as an "Super-Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the item <item>
    And another user is editing the item <item>
    Then I see a notification of editing item
    And I see the all of the buttons are disabled
    And the item is marked in the tree
    When Show link selected from the notification
    Then that item is selected

Feature: Change Notifications
  When an item is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0812 @change-notification @ui
  Scenario: 0108-0812 Notification for item being edited when logged in as an User
    Given I am logged in as an <User>
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the item <item>
    And another user is editing the item <item>
    Then I see a notification of editing item
    And I see the all of the buttons are disabled
    And the item is marked in the tree
    When Show link selected from the notification
    Then that item is selected

Feature: Change Notifications
  When another user finishes editing the item then I see all of the buttons are enabled

  @0108-0813 @change-notification @ui @organization-admin
  Scenario: 0108-0813 Notification for item finished editing and when logged in as an Organization Admin
    Given I am logged in as an "Admin"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the item <item>
    And another user finishes editing the item <item>
    Then I see a notification of modified item
    And I see the all of the buttons are enabled
    And the item is no longer marked in the tree
    When Show link is selected from the notification
    Then that item is selected

Feature: Change Notifications
  When another user finishes editing the item then I see all of the buttons are enabled

  @0108-0814 @change-notification @ui @organization-editor
  Scenario: 0108-0814 Notification for item finished editing and when logged in as an Organization Editor
    Given I am logged in as an "Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the item <item>
    And another user finishes editing the item <item>
    Then I see a notification of modified item
    And I see the all of the buttons are enabled
    And the item is no longer marked in the tree
    When Show link selected from the notification
    Then that item is selected

Feature: Change Notifications
  When another user finishes editing the item then I see all of the buttons are enabled

  @0108-0815 @change-notification @ui @super-user
  Scenario: 0108-0815 Notification for item finished editing and when logged in as a Super User
    Given I am logged in as an "Super-User"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the item <item>
    And another user finishes editing the item <item>
    Then I see a notification of modified item
    And I see the all of the buttons are enabled
    And the item is no longer marked in the tree
    When Show link selected from the notification
    Then that item is selected

Feature: Change Notifications
  When another user finishes editing the item then I see all of the buttons are enabled

  @0108-0816 @change-notification @ui @super-editor
  Scenario: 0108-0816 Notification for item finished editing and when logged in as a Super Editor
    Given I am logged in as a "Super-Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the item <item>
    And another user finishes editing the item <item>
    Then I see a notification of modified item
    And I see the all of the buttons are enabled
    And the item is no longer marked in the tree
    When Show link selected from the notification
    Then that item is selected

Feature: Change Notifications
  When another user finishes editing the item then I see all of the buttons are enabled

  @0108-0817 @change-notification @ui
  Scenario: 0108-0817 Notification for item finished editing and when logged in as a User
    Given I am logged in as a <User>
    And a framework <frameworkName> exists
    When I edit the framework
    And I select the item <item>
    And another user finishes editing the item <item>
    Then I see a notification of modified item
    And I see the all of the buttons are enabled
    And the item is no longer marked in the tree
    When Show link selected from the notification
    Then that item is selected

Feature: Change Notifications
  When a user deletes an item, Item Delete Notifications are displayed

  @0108-0818 @change-notification @ui @organization-admin
  Scenario: 0108-0818 Item Delete Notifications when logged in as an Organization Admin
    Given I am logged in as a "Admin"
    And a framework <frameworkName> exists
    When I edit the framework
    And another user deletes the item <item>
    Then I see a notification of deleted item
    And the item is removed from the tree

Feature: Change Notifications
  When a user deletes an item, Item Delete Notifications are displayed

  @0108-0819 @change-notification @ui @organization-editor
  Scenario: 0108-0819 Item Delete Notifications when logged in as an Organization Editor
    Given I am logged in as a "Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And another user deletes the item <item>
    Then I see a notification of deleted item
    And the item is removed from the tree

Feature: Change Notifications
  When a user deletes an item, Item Delete Notifications are displayed

  @0108-0820 @change-notification @ui @super-user
  Scenario: 0108-0820 Item Delete Notifications when logged in as an Super User
    Given I am logged in as a "Super-User"
    And a framework <frameworkName> exists
    When I edit the framework
    And another user deletes the item <item>
    Then I see a notification of deleted item
    And the item is removed from the tree

Feature: Change Notifications
  When a user deletes an item, Item Delete Notifications are displayed

  @0108-0821 @change-notification @ui @super-editor
  Scenario: 0108-0821 Item Delete Notifications when logged in as an Super Editor
    Given I am logged in as a "Super-Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And another user deletes the item <item>
    Then I see a notification of deleted item
    And the item is removed from the tree

Feature: Change Notifications
  When a user creates an item, Item Create Notifications are displayed

  @0108-0822 @change-notification @ui @organization-admin
  Scenario: 0108-0822 Item Create Notifications when logged in as an Organization Admin
    Given I am logged in as a "Admin"
    And a framework <frameworkName> exists
    When I select the <frameworkName> framework
    And another user creates the item <item>
    Then I see a notification of new item
    And the item will appear in the tree
    When Show link is selected from the notification
    Then that item is selected

Feature: Change Notifications
  When a user creates an item, Item Create Notifications are displayed

  @0108-0823 @change-notification @ui @organization-editor
  Scenario: 0108-0823 Item Create Notifications when logged in as an Organization Editor
    Given I am logged in as a "Editor"
    And a framework <frameworkName> exists
    When I select the <frameworkName> framework
    And another user creates the item <item>
    Then I see a notification of new item
    And the item will appear in the tree
    When Show link is selected from the notification
    Then that item is selected

Feature: Change Notifications
  When a user creates an item, Item Create Notifications are displayed

  @0108-0824 @change-notification @ui @super-user
  Scenario: 0108-0824 Item Create Notifications when logged in as an Super User
    Given I am logged in as a "Super-User"
    And a framework <frameworkName> exists
    When I select the <frameworkName> framework
    And another user creates the item <item>
    Then I see a notification of new item
    And the item will appear in the tree
    When Show link is selected from the notification
    Then that item is selected

Feature: Change Notifications
  When a user creates an item, Item Create Notifications are displayed

  @0108-0825 @change-notification @ui @super-editor
  Scenario: 0108-0825 Item Create Notifications when logged in as an Super Editor
    Given I am logged in as a "Super-Editor"
    And a framework <frameworkName> exists
    When I select the <frameworkName> framework
    And another user creates the item <item>
    Then I see a notification of new item
    And the item will appear in the tree
    When Show link is selected from the notification
    Then that item is selected

Feature: Change Notifications
  When a user moves an item, Item Move Notifications are displayed

  @0108-0826 @change-notification @ui @organization-admin
  Scenario: 0108-0826 Item Move Notifications when logged in as an Organization Admin
    Given I am logged in as a "Admin"
    And a framework <frameworkName> exists
    When I edit the framework
    And another user moves an <item>
    Then I see a notification of moved item
    And the item will be moved in the tree
    When Show link is selected from the notification
    Then that item is selected

Feature: Change Notifications
  When a user moves an item, Item Move Notifications are displayed

  @0108-0827 @change-notification @ui @organization-editor
  Scenario: 0108-0827 Item Move Notifications when logged in as an Organization Editor
    Given I am logged in as a "Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And another user moves an <item>
    Then I see a notification of moved item
    And the item will be moved in the tree
    When Show link is selected from the notification
    Then that item is selected

Feature: Change Notifications
  When a user moves an item, Item Move Notifications are displayed

  @0108-0828 @change-notification @ui @super-user
  Scenario: 0108-0828 Item Move Notifications when logged in as an Super User
    Given I am logged in as a "Super-User"
    And a framework <frameworkName> exists
    When I edit the framework
    And another user moves an <item>
    Then I see a notification of moved item
    And the item will be moved in the tree
    When Show link is selected from the notification
    Then that item is selected

Feature: Change Notifications
  When a user moves an item, Item Move Notifications are displayed

  @0108-0829 @change-notification @ui @super-editor
  Scenario: 0108-0829 Item Move Notifications when logged in as an Super Editor
    Given I am logged in as a "Super-Editor"
    And a framework <frameworkName> exists
    When I edit the framework
    And another user moves an <item>
    Then I see a notification of moved item
    And the item will be moved in the tree
    When Show link is selected from the notification
    Then that item is selected

Feature: Change Notifications
  When a user starts to edit an object, the object will be locked for about 5 minutes (? 1 minute) from the last interaction the editing user has with the form

  @0108-0830 @change-notification @ui @organization-editor
  Scenario: 0108-0830 After 5 minutes of an item being edited, the unsaved changes are gone - Logged in as an Org Editor
    Given I am logged in as an "Editor"
    And a framework <frameworkName> exists
    When I select the item <item>
    And I Edit the item <item>
    And make changes to a field <field>
    Then after 5 minutes when the timeout occurs, a pop up with a message <message> displays
    And I cannot save the changes made as the Save button is greyed out
    And the unsaved changes are gone
    When I select the item <item> again
    And I Edit the item <item>
    And make changes to a field <field>
    And I click save changes
    Then the changes are saved

Feature: Change Notifications
  When a user starts to edit an object, the object will be locked for about 5 minutes (? 1 minute) from the last interaction the editing user has with the form

  @0108-0831 @change-notification @ui @organization-admin
  Scenario: 0108-0831 After 5 minutes of an item being edited, the unsaved changes are gone - Logged in as an Org Admin
    Given I am logged in as an "Admin"
    And a framework <frameworkName> exists
    When I select the item <item>
    And I Edit the item <item>
    And make changes to a field <field>
    Then after 5 minutes when the timeout occurs, a pop up with a message <message> displays
    And I cannot save the changes made as the Save button is greyed out
    And the unsaved changes are gone
    When I select the item <item> again
    And I Edit the item <item>
    And make changes to a field <field>
    And I click save changes
    Then the changes are saved

Feature: Change Notifications
  When a user starts to edit an object, the object will be locked for about 5 minutes (? 1 minute) from the last interaction the editing user has with the form

  @0108-0832 @change-notification @ui @super-user
  Scenario: 0108-0832 After 5 minutes of an item being edited, the unsaved changes are gone - Logged in as a Super User
    Given I am logged in as an "Super-User"
    And a framework <frameworkName> exists
    When I select the item <item>
    And I Edit the item <item>
    And make changes to a field <field>
    Then after 5 minutes when the timeout occurs, a pop up with a message <message> displays
    And I cannot save the changes made as the Save button is greyed out
    And the unsaved changes are gone
    When I select the item <item> again
    And I Edit the item <item>
    And make changes to a field <field>
    And I click save changes
    Then the changes are saved

Feature: Change Notifications
  When a user starts to edit an object, the object will be locked for about 5 minutes (? 1 minute) from the last interaction the editing user has with the form

  @0108-0833 @change-notification @ui @super-editor
  Scenario: 0108-0833 After 5 minutes of an item being edited, the unsaved changes are gone - Logged in as a Super Editor
    Given I am logged in as an "Super-Editor"
    And a framework <frameworkName> exists
    When I select the item <item>
    And I Edit the item <item>
    And make changes to a field <field>
    Then after 5 minutes when the timeout occurs, a pop up with a message <message> displays
    And I cannot save the changes made as the Save button is greyed out
    And the unsaved changes are gone
    When I select the item <item> again
    And I Edit the item <item>
    And make changes to a field <field>
    And I click save changes
    Then the changes are saved
