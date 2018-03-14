Features: checkout 
  In order to buy product 
  As a customer 
  I need to be able to checkout the selected products 
 

Scenario: order several products 
 Given I have product with $600 price in my cart 
 And I have product with $1000 price in my cart 
 When  I go to checkout process 
 Then I should see that total number of products is 2 
 And My order amount is $1600 
