Feature: The framework can be downloaded
  In order to load a framework into another server
  As an anonymous user
  I need to download a CASE file of the framework

  @smoke @anonymous @case-file
  Scenario: An anonymous can see a framework
    Given I am on a framework page
    When I download the framework CASE file
    Then I should see content in the CASE file

