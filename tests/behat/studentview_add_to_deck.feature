@mod @mod_flashcards

Feature: As a student I want to add flashcards to my deck

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
    And I follow "Test flash cards"
    And I click on "Create new flash card" "button"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Solution | This is the answer |
    And I click on "Save changes" "button"
    Then I should see "flashcard 1"
    And I click on "Create new flash card" "button"
    And I set the following fields to these values:
      | Question name | flashcard 2 |
      | Question text | This is question 2 |
      | Solution | This is answer 2 |
    And I click on "Save changes" "button"
    And I log out

  @javascript
  Scenario: Ich klicke auf den zweiten Reiter
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test flash cards"
    And I follow "Customize your flashcard deck"
    Then I should see "flashcard 1"
    And I follow "My flashcards"
    Then I should see "Nothing"
    And I follow "Flashcards collection"
    And I click on "input[class^=mod-flashcards-checkbox]" "css_element" in the "flashcard 1" "table_row"
    And I click on "Add flashcard(s)" "button"
    And I follow "Test flash cards"
    And I follow "Customize your flashcard deck"
    And I follow "My flashcards"
    Then I should see "flashcard 1"
    And I should not see "flashcard 2"
    And I follow "Flashcards collection"
    Then I should not see "flashcard 1"
    And I should see "flashcard 2"
    Then I log out
