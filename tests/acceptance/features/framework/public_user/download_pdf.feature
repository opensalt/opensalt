Feature: The framework can be downloaded in the form of pdf
  In order to load a framework into another server
  As an anonymous user
  I need to download a pdf file of the framework

  @incomplete @anonymous @pdf-file @7620-0007 @skip @broken
  Scenario: 0908-0000 An anonymous can see a framework
    Given I am on a framework page
    When I download the framework PDF file
    Then I should see content in the PDF file
