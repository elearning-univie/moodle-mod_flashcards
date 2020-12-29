@mod @mod_flashcards @amc @mod_flashcards

Feature: The Authordisplay option works as intended if a teacher enters a card

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
    And I follow "Test flash cards"
    And I click on "Create new flash card" "button"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Default mark | 1 |
      | Solution | This is a solution |
    And I click on "Save changes" "button"
   Then I should see "This is a question"
   Then I log out

  @javascript
  Scenario: Authordisplay is set to "Type of Author" and I see a teacher flashcard
    Given the following config values are set as admin:
      | authordisplay | 1 | flashcards |
   When I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Test flash cards"
    And I follow "Flash card collection"
   Then I should see "Teacher" in the ".flashcardsstudenttable" "css_element"
    And I should not see "John Doe" in the ".flashcardsstudenttable" "css_element"

  @javascript 
Scenario: Authordisplay is set to "Name of Author" and I see a teacher flashcard
    Given the following config values are set as admin:
      | authordisplay | 2 | flashcards |
   When I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Test flash cards"
    And I follow "Flash card collection"
   Then I should see "John Doe" in the ".flashcardsstudenttable" "css_element"
    And I should not see "Teacher" in the ".flashcardsstudenttable" "css_element"

  @javascript 
Scenario: Authordisplay is set to "disabled" and I see a teacher flashcard
    Given the following config values are set as admin:
      | authordisplay | 0 | flashcards |
   When I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Test flash cards"
    And I follow "Flash card collection"
   Then I should not see "John Doe" in the ".flashcardsstudenttable" "css_element"
    And I should not see "Teacher" in the ".flashcardsstudenttable" "css_element"