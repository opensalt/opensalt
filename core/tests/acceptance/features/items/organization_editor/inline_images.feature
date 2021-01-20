Feature: Adding an item in order to confirm the application
    can add an inline image as an editor user

  @skip @editor @item @add-item
  Scenario: An editor user can add an inline image
    Given I log in as a user with role "Editor"
    When I create a framework
    And I am filling the fields
    And I drag and drop an image to the WYSWYG editor
    And I save the item

    Then I should see the image in the full statement
