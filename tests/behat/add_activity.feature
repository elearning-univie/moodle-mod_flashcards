@mod @mod_flashcards @amc

Feature: As a teacher I want to add an flashcards activity

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | Teacher   | Teacher  | teacher@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: I add a flashcards activity
    When I add a "Flash cards" to section "1" and I fill the form with:
      | Flash cards activity name | Test flash cards |
    And I follow "Test flash cards"
    Then I should see "Add flash card"
