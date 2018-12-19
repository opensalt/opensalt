Feature: create custom fields

    @super-user
    Scenario: A super user can create additional fields
        Given I log in as a user with role "Super-User"
        Then I create the custom field "test_additionalfield"
        And I am on the page "/cfdoc"
        Then I should see "Create a new Framework" button

        When I create a framework
        And I add a Item
        Then I should see the framework
        Then I download the framework excel file
        Then I add custom fields via spreadsheet

        Then I delete the framework
        Then I delete the custom field "test_additionalfield"
