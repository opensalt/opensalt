Feature: Session timeout warning
  In order to not become unknowningly logged out
  As a user
  I need to get a warning telling me that my session is going to expire

  @manual @skip @super-editor @0503-1338 @duplicate
  Scenario: 0503-1338 See session expiry warning
    Given I am logged in as a "Super-Editor"
    When I am idle until 5 minutes before my session ends
    Then I will be shown a message that my session is ending soon
    When I am idle until 1 minute before my session ends
    Then I will be shown a message that my session is ending very soon
    When I am idle until 10 seconds before my session ends
    Then I will be shown a message that my session is ending
    When I am idle until after my session ends
    Then I will be shown a message that my session has ended

  @manual @skip @super-editor @0503-1339 @duplicate
  Scenario: 0503-1339 Renew session after first expiry warning
    Given I am logged in as a "Super-Editor"
    When I am idle until 5 minutes before my session ends
    Then I will be shown a message that my session is ending soon
    When I click "Renew Session"
    Then the session idle timer will be reset

  @manual @skip @super-editor @0503-1340 @duplicate
  Scenario: 0503-1340 Renew session after first expiry warning
    Given I am logged in as a "Super-Editor"
    When I am idle until 5 minutes before my session ends
    Then I will be shown a message that my session is ending soon
    When I am idle until 1 minute before my session ends
    Then I will be shown a message that my session is ending very soon
    When I click "Renew Session"
    Then the session idle timer will be reset
