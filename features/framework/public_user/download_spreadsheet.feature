Feature: The framework can be downloaded
  In order to load a framework into another server
  As an anonymous user
  I need to download a Spreadsheet file of the framework

  @incomplete @anonymous @spreadsheet-file @0901-0002
  Scenario: 0901-0000 An anonymous can see a framework
    Given I am on a framework page
    When I download the framework spreadsheet file
    Then I should see content in the spreadsheet file

