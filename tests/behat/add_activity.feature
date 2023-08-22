@mod @mod_flashcards

Feature: As a teacher I want to add a flashcards activity

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Flash cards" to section "1" and I fill the form with:
      | Flash cards activity name | Test flash cards |
    And I log out

  @javascript
  Scenario: I add a flashcard as a teacher
    Given I am on the "Test flash cards" "flashcards activity" page logged in as "teacher1"
    And I click on "Create new flash card" "button"
    Then I should see "Editing a Flashcard question"
    And I should see "Question name"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Solution | This is the answer |
    And I click on "Save changes" "button"
    Then I should see "flashcard 1"
    And I log out

  @javascript
  Scenario: I add a flashcard as a student
    Given I am on the "Test flash cards" "flashcards activity" page logged in as "student1"
    And I follow "Create/customize my flashcards"
    And I click on "Create new flash card" "button"
    Then I should see "Editing a Flashcard question"
    And I should see "Question name"
    And I set the following fields to these values:
      | Question name | flashcard 2 |
      | Question text | This is a question |
      | Solution | This is the answer |
    And I click on "Save changes" "button"
    Then I should see "flashcard 2"
