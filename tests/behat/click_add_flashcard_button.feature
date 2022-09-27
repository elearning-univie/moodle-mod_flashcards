@mod @mod_flashcards @amc

Feature: As a student I can add a flashcard

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | John   | Doe  | teacher@example.com |
      | student  | Derpina   | Knowsalot  | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Flash cards" to section "1" and I fill the form with:
      | Flash cards activity name | Test flash cards |
    And I log out
    Then I log in as "student"

  @javascript
  Scenario: I add a flashcard as a student
    Given I am on the "Test flash cards" "flashcards activity" page
    And I follow "Create/customize my flashcards"
    And I click on "Create new flash card" "button"
    Then I should see "Editing a Flashcard question"
    And I should see "Question name"
