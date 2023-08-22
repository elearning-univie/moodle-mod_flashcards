@mod @mod_flashcards

Feature: As a student I open the flashcard preview

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
    And I am on the "Test flash cards" "flashcards activity" page
    And I follow "Create/customize my flashcards"
    And I click on "Create new flash card" "button"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Solution | This is a solution |
    And I click on "Save changes" "button"
    Then I should see "flashcard 1"
    Then I log out

  @javascript
  Scenario: View Flashcard preview as Student
    When I log in as "student1"
    And I am on the "Test flash cards" "flashcards activity" page
    And I follow "Create/customize my flashcards"
    And I click on ".mod_flashcards_questionpreviewlink" "css_element"
    And I switch to a second window
    Then I should see "Show solution"
    And I should see "Teacher check"
    And I should see "Peer review"
    And I should see "not yet evaluated"
