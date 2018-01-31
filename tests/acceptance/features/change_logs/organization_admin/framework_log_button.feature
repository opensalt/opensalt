Feature: Change Log Interaction
  New button <Log View> to view logs

  @0117-0701 @logs @ui @organization-admin @duplicate
  Scenario: 0117-0701 Log View shows the table of data elements - Logged in as Organization Admin
    Given I am logged in as an "Admin"
    When I create a framework
    Then I see the Log View button in the title section
    When I select Log View
    Then I see the log table
    And I see all of the data table elements
    Then I delete the framework

