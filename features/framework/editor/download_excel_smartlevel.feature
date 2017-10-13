Feature: The excel export contains smart level values
  In order to manipulate frameworks
  As an editor
  I need to export an excel file

  @smoke @editor @case-file @smartlevel @framework
  Scenario: A CASE file contains smart levels
    Given I log in as a user with role "Editor"
    And I am on the homepage
    When I click "Import framework"
    Then I should see the import dialogue
    When I click "Import CASE file"
    And I upload the test smartlevel framework
    And I go to the uploaded framework
    And I download the framework excel file
    Then I should have an excel file with smart levels

