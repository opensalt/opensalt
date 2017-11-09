Feature: Edit an existing Organization
  In order to a edit a Organization
  As an organization admin
  I need to have access to the Organizations List page

  @incomplete @admin @org @edit-org
  Scenario Outline: 1011-1728 Editing an Organization in Organizations List
    Given I log in as a user with role "Admin"
    And I am on the Organization list page
    And I click on "edit" button for organization "<org>"
    And I fill in name with "<orgNew>"
    And I click on "Save" button
    Then I should see "<orgNew>" in organization list

    Examples:
      | org | orgNew  |
      | PCG | New PCG |