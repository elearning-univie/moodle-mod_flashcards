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
    And I click on "Save changes" "button" in the "#fgroup_id_buttonar" "css_element"

  @javascript
  Scenario: Close Flashcard preview as Teacher
    And I click on ".mod_flashcards_questionpreviewlink" "css_element"
    And I switch to the browser tab opened by the app
    Then I should see "Flip card"
    And I click on "Close preview" "button"
    And I switch to window "Moodle Flashcard"
    And I pause
    And I should see "Test flash cards"
