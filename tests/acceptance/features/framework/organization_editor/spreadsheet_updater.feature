Feature: Updating a framework via spreadsheet

    @organization-editor
    Scenario: An organization-editor can edit a framework with a spreadsheet
        Given I log in as a user with role "Editor"
        Then I should see "Create a new Framework" button

        When I create a framework
        And I add a Item
        Then I import children
        Then I should see the framework
        Then I download the framework excel file
        Then I update the framework via spreadsheet
