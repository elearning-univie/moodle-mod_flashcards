@mod @mod_flashcards

Feature: As a student I edit the an existing flashcard

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | John   | Doe  | teacher@example.com |
      | student1  | Derpina   | Knowsalot  | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Flash cards" to section "1" and I fill the form with:
      | Flash cards activity name | Test flash cards |
    And I log out
    Then I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test flash cards"
    And I follow "Propose novel flashcards"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Solution | This is a solution |
    And I click on "Save changes" "button"
    Then I should see "flashcard 1"
    And I follow "View"
    And I click on "Yes"
    And I click on "Close Preview"
    Then I log out

  @javascript
  Scenario: Edit Flashcard as Student
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test flash cards"
    And I follow "Customize your flashcard deck"
    And I follow "Edit"
    And I select "marginal changes"
    And I click "Save changes"
    Then I should see "1/0"

  @javascript
  Scenario: Edit Flashcard as Student with substantial changes
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test flash cards"
    And I follow "Customize your flashcard deck"
    And I follow "Edit"
    And I select "substantial changes"
    And I click "Save changes"
    Then I should see "0/0"
