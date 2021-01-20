Feature: create a custom field from the form

    @super-user
    Scenario: A super user can create items with additional fields
        Given I log in as a user with role "Super-User"
        Then I create the custom field "test_additionalfield"
        And I am on the page "/cfdoc"
        Then I should see "Create a new Framework" button

        When I create a framework
        Then I add "test_item" item with custom field "test_additionalfield" and value "cf_test"
        Then I should see the framework
        Then I see the additional field "test_additionalfield" in the item "test_item" with value "cf_test"

        Then I delete the framework
        Then I delete the custom field "test_additionalfield"
