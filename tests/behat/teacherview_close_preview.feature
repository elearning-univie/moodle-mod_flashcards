@mod @mod_flashcards @amc

Feature: As a teacher I close the flashcard preview

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | John   | Doe  | teacher@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Flash cards" to section "1" and I fill the form with:
      | Flash cards activity name | Test flash cards |
    And I follow "Test flash cards"
    And I click on "Create new flash card" "button"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Solution | This is a solution |
    And I click on "Save changes" "button"
    Then I log out

  @javascript
  Scenario: Close Flashcard preview as Teacher
    When I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Test flash cards"
    And I click on "Preview" "button"
    Then I should see "Flip card"
    And I click on "Close preview" "button"
    And I should see "Test flash cards"
