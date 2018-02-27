Feature: Download comment report csv file
  Comment Report Export

  @0117-0725 @logs @ui @super-user @comment @csv @duplicate
  Scenario: 1016-1346 A CSV file can be downloaded
    Given I log in as a user with role "Admin"
    Given I am on a framework page
    And I added comments on DocItem
    And I added comments on CFItem
    And I download the comment report CSV
    Then I can see the comment data in the CSV matches the data in the comment section