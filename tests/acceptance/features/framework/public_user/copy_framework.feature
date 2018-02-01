Feature: Copying framework content to another framework

  @super-user @framework @copy-framework @1016-1323 @duplicate
  Scenario: An anonymous user can not see copy framework button
    Given I am on a framework page
    Then I should not see the copy framework modal button

