Feature: Cloning a framework

  @super-user @framework @clone-framework @1034-1434 @duplicate
  Scenario: 1034-1434 An anonymous user can not see clone framework button
    Given I am on a framework page
    Then I should not see the clone framework modal button
