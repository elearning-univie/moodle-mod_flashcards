@mod @mod_flashcards @amc

Feature: As a teacher i want to test the learn now function

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
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Flash cards" to section "1" and I fill the form with:
      | Flash cards activity name | Test flash cards |
    And I follow "Test flash cards"
    And I click on "Create flash card" "button"
    And I set the following fields to these values:
      | Question name | flashcard 1 |
      | Question text | This is a question |
      | Solution | This is a solution |
    And I click on "Save changes" "button"
    And the following config values are set as admin:
      | contextlocking | 1 |
    And I am on the "C1" "Course" page
    And I follow "Actions menu"
    And I follow "Freeze this context"
    And I click on "Continue" "button"
    And I log out

  @javascript
  Scenario: I switch role to student and test learn now
    Given I am on the "C1" "Course" page logged in as "teacher"
    When I follow "Switch role to..." in the user menu
    And I press "Student"
    And I follow "Test flash cards"
    And I follow "Customize your flashcard deck"
    And I click on "selectall" "checkbox"
    And I click on "Add flash card" "button"
    And I click on "Start learning" "button"
    Then I should see "This is a question"
