Feature: Fetch matches from API
  In order to tag a resource
  As another system
  I can fetch a list of all exact matches of an identifier

  @api @0606-1252
  Scenario: 0606-1252
    Given I log in as a user with role "Editor"
    And I create a framework with a remembered creator

    When I add the item "first"
    And I add the item "second"
    And I add the item "third"

    And I add an "Exact Match Of" association from "second" to "first"
    And I add an "Exact Match Of" association from "third" to "first"

    And I get the exact matches of "third"
    Then I see the identifier for "second" in the list of exact matches


    Then I delete the framework
