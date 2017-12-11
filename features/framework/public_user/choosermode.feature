Feature: The framework is viewable in "chooser mode" to allow an external app to include opensalt in an iframe for the purposes of choosing a competency and receiving back the specification for the chosen competency
  In order to confirm the application is up and running
  As an anonymous user
  I need to be able to view and interact with a framework in chooser mode

  @smoke @anonymous @chooser-mode @0901-0004
  Scenario: 0901-0001 An anonymous user can see a framework
    Given I am viewing a framework page in chooser mode, by adding "?mode=chooser" to the url for a framework
    Then I should see the framework tree (the "left side" of the canonical view mode) alone on the screen, with a search bar at the top
    And I should be able to browse and search the framework
    And when I click on an item, I should see a popup with "Show Details" and "Choose" buttons
    And when I click "Show Details" I should see the details for that item (what would normally be available on the right side of the screen in the canonical view mode)
