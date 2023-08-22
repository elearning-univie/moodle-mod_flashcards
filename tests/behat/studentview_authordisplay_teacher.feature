@mod @mod_flashcards

Feature: As a student I want to see the teachers name, if a teacher creates a flashcard

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
    And I am on the "Test flash cards" "flashcards activity" page
    And I click on "Create new flash card" "button"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Solution | This is a solution |
    And I click on "Save changes" "button"
    Then I should see "flashcard 1"
    Then I log out

  @javascript
  Scenario: Authordisplay is set to "Type of Author" and I see a teacher flashcard
    Given the following config values are set as admin:
      | authordisplay | 1 | flashcards |
    When I log in as "student"
    And I am on the "Test flash cards" "flashcards activity" page
    And I follow "Create/customize my flashcards"
    Then I should see "John Doe (Teacher)"

  @javascript
  Scenario: Authordisplay is set to "Name of Author" and I see a teacher flashcard
    Given the following config values are set as admin:
      | authordisplay | 2 | flashcards |
    When I log in as "student"
    And I am on the "Test flash cards" "flashcards activity" page
    And I follow "Create/customize my flashcards"
    Then I should see "John Doe"

  @javascript
  Scenario: Authordisplay is set to "disabled" and I see a teacher flashcard
    Given the following config values are set as admin:
      | authordisplay | 0 | flashcards |
    When I log in as "student"
    And I am on the "Test flash cards" "flashcards activity" page
    And I follow "Create/customize my flashcards"
    Then I should not see "John Doe"
