Feature: Aws Storage
  In order to confirm the application can add a new item
  As an super user
  I need to see a aws attachment fields

  @super-user @item @add-item @1017-0928 @duplicate
  Scenario: 1016-0929 An super user can add a item
    Given I log in as a user with role "Super-User"

    When I see attachment
    Then I download attachment
