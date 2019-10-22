<?php
function flashcards_add_instance($flashcards) {
    global $DB;
    $object = new stdClass();
    $object->timecreated = time();
    if (property_exists($flashcards, 'intro') || $flashcards->intro == null) {
        $object->intro = '';
    } else {
        $object->intro = $flashcards;
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