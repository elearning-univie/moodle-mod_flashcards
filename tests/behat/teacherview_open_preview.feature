@mod @mod_flashcards

Feature: As a teacher I close the flashcard preview

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email        |
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
    And I am on the "Test flash cards" "flashcards activity" page
    And I click on "Create new flash card" "button"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Solution | This is a solution |
    And I click on "Save changes" "button" in the "#fgroup_id_buttonar" "css_element"

  @javascript
  Scenario: Close Flashcard preview as Teacher
    When I click on ".mod_flashcards_questionpreviewlink" "css_element"
    And I switch to a second window
    Then I should see "Show solution"
    And I should see "Teacher check"
    And I should see "Peer review"

  @javascript
  Scenario: Set Flashcard Review as Teacher
    When I click on ".mod_flashcards_questionpreviewlink" "css_element"
    And I switch to a second window
    And I should see "correct"
    And I select "not yet evaluated" from the "teachercheck" singleselect
    And I reload the page
    Then I should see "not yet evaluated"
