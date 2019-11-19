<?php
function flashcards_add_instance($flashcards) {
    global $DB;
    $object = new stdClass();
    $object->timecreated = time();
    if (property_exists($flashcards, 'intro') || $flashcards -> intro == null) {
        $flashcards -> intro = '';
    } else {
        $flashcards -> intro = $flashcards;
    }

    $catids = explode(",", $flashcards->category);

    if ($flashcards->newcategory) {
        $newcat = new stdClass();
        $newcat->name = $flashcards->newcategoryname;
        $newcat->contextid = $catids[1];
        $newcat->info = 'Created via Flashcard Activity';
        $qcid = $DB->insert_record('question_categories', $newcat);
        $flashcards->categoryid = $qcid;
    }

    $object = $DB->insert_record('mod_flashcards', $object);
    return;
}
function flashcards_update_instance($flashcards) {
    
}
function flashcards_delete_instance($id) {
    global $DB;

    $DB->delete_records('flashcards', ['id' => $id]);
}