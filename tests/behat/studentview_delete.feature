@mod @mod_flashcards @amc @mod_flashcards

Feature: As a student I can delete my own questions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | John   | Doe  | teacher@example.com |
      | student  | Student   | Student  | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "Flash cards" to section "1" and I fill the form with:
      | Flash cards activity name | Test flash cards |
    Then I log out

  @javascript
  Scenario: Students can not delete teacher questions
    When I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Test flash cards"
    And I click on "Create flash card" "button"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Solution | This is a solution |
    And I click on "Save changes" "button"
    Then I should see "This is a question"
    And I log out
    Then I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Test flash cards"
    And I follow "Customize your flashcard deck"
    Then ".mod_flashcards_studentview_delete" "css_element" should not exist

  @javascript
  Scenario: Students can delete their own questions
    Then I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Test flash cards"
    And I follow "Propose novel flashcards"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Solution | This is a solution |
    And I click on "Save changes" "button"
    Then ".mod_flashcards_studentview_delete" "css_element" should exist
    And I click on ".mod_flashcards_studentview_delete" "css_element"
    Then ".mod_flashcards_studentview_delete" "css_element" should not exist
