Feature: Copying framework content to another framework

  @super-user @framework @copy-framework @1034-1434 @duplicate
  Scenario: 1034-1434 An anonymous user can not see copy framework button
    Given I am on a framework page
    Then I should not see the copy framework modal button

