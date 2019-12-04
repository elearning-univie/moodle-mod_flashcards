<?php
$services = array(
        'flashcardsservice' => array(
                'functions' => array ('mod_flashcards_update_progress'),
                'requiredcapability' => 'mod/flashcards:studentview',
                'restrictedusers' =>0,
                'enabled'=>1,
        )
);

$functions = array(
        'mod_flashcards_update_progress' => array(
                'classname'   => 'mod_flashcards_external',
                'methodname'  => 'update_progress',
                'classpath'   => 'mod/flashcards/externallib.php',
                'description' => 'Update question progress of a student',
                'type'        => 'write',
                'ajax' => true,
                'loginrequired' => true
        ),
);