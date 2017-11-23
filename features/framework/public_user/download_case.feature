Feature: The framework can be downloaded
  In order to load a framework into another server
  As an anonymous user
  I need to download a CASE file of the framework

  @smoke @anonymous @case-file @0901-0000
  Scenario: 0901-0000 An anonymous can see a framework
    Given I am on a framework page
    When I download the framework CASE file
    Then I should see content in the CASE file

