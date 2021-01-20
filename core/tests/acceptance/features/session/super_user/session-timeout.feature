Feature: Session timeout warning
  In order to not become unknowningly logged out
  As a user
  I need to get a warning telling me that my session is going to expire

  @manual @skip @super-user @0503-1335 @duplicate
  Scenario: 0503-1335 See session expiry warning
    Given I am logged in as a "Super-User"
    When I am idle until 5 minutes before my session ends
    Then I will be shown a message that my session is ending soon
    When I am idle until 1 minute before my session ends
    Then I will be shown a message that my session is ending very soon
    When I am idle until 10 seconds before my session ends
    Then I will be shown a message that my session is ending
    When I am idle until after my session ends
    Then I will be shown a message that my session has ended

  @manual @skip @super-user @0503-1336 @duplicate
  Scenario: 0503-1336 Renew session after first expiry warning
    Given I am logged in as a "Super-User"
    When I am idle until 5 minutes before my session ends
    Then I will be shown a message that my session is ending soon
    When I click "Renew Session"
    Then the session idle timer will be reset

  @manual @skip @super-user @0503-1337 @duplicate
  Scenario: 0503-1337 Renew session after first expiry warning
    Given I am logged in as a "Super-User"
    When I am idle until 5 minutes before my session ends
    Then I will be shown a message that my session is ending soon
    When I am idle until 1 minute before my session ends
    Then I will be shown a message that my session is ending very soon
    When I click "Renew Session"
    Then the session idle timer will be reset
