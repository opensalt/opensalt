Feature: Download comment report csv file
  Comment Report Export

  @1011-0800 @ui @admin @comments @csv
  Scenario: 1011-0800 A CSV file can be downloaded containing comments
    Given "comments" is enabled
    And I log in as a user with role "Admin"
    And I am on a framework page
    When I added comments on DocItem
    And I added comments on CFItem
    And I download the comment report CSV
    Then I can see the comment data in the CSV matches the data in the comment section
