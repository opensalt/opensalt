Feature: Enable upload attachment file    

    @super-user @framework @2063-2069
    Scenario: Enable upload attachment file
        Given "enable_filesystem" is enabled
        Given I log in as a user with role "Super-User"
        When I create a framework
        And I add new child the fields in a framework
        And I delete the framework 

    


