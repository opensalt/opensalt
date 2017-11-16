Feature: Search a item
  In order to confirm the application can copy item
  As an editor user
  I need to see the search from

  @incomplete @editor @item @search-item @1016-1018
  Scenario: 1016-1018 An editor user can edit a item
    Given I log in as a user with role "Editor"
    Then I upload a excel framework
    And I search for "RL.K.1" Items
    And I should see "RL.K.1" Items
    Then I delete "Draft" framework