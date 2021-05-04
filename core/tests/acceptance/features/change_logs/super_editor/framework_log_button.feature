Feature: Change Log Interaction
  New button <Log View> to view logs

  @0117-0713 @logs @ui @super-editor @duplicate
  Scenario: 0117-0713 Log View shows the table of data elements - Logged in as Super Editor
    Given I am logged in as an "Super-Editor"
    When I create a framework
    Then I see the Log View button in the title section
    When I select Log View
    Then I see the log table
    And I see all of the data table elements
    Then I delete the framework

