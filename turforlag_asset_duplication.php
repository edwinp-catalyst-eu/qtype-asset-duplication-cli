<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script copies turmultiplechoice question type audio and image files from original course to restored course.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2016 Catalyst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

// Run from /admin/cli dir
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'sourcecourseid' => false, 
        'destinationcourseid' => '', 
        'help' => false,
    ),
    array(
        'src' => 'sourcecourseid',
        'dest' => 'destinationcourseid',
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = <<<EOL
Performs transfer of turmultiplechoice question type audio and image files from original course to restored course.

Options:
-src=INTEGER, --sourcecourseid=INTEGER          Course ID for backup.
-dest=INTEGER, --destinationcourseid=INTEGER    Course shortname for backup.
-h, --help                                      Print out this help.

Example:
\$sudo -u www-data /usr/bin/php admin/cli/turmultiplechoice-file-transfer.php --sourcecourseid=70 --destinationcourseid=71\n
EOL;

    echo $help;
    die;
}

if (!($options['sourcecourseid'] && $options['destinationcourseid'])) {
    $help = <<<EOL
Options sourcecourseid and destinationcourseid are both required.

Please use --help option.\n
EOL;

    echo $help;
    die;
}

$admin = get_admin();
if (!$admin) {
    mtrace("Error: No admin account was found");
    die;
}

// Check that the course exists.
if ($options['sourcecourseid']) {
    $course = $DB->get_record('course', array('id' => $options['sourcecourseid']), '*', MUST_EXIST);
}

// Turmultiplechoice question types.
$turmultiplechoicesourcequestionids = question_ids($options['sourcecourseid'], 'turmultiplechoice');
$turmultiplechoicedestinationquestionids = question_ids($options['destinationcourseid'], 'turmultiplechoice');

// Turprove question types.
$turprovesourcequestionids = question_ids($options['sourcecourseid'], 'turprove');
$turprovedestinationquestionids = question_ids($options['destinationcourseid'], 'turprove');

$sourcequestionids = array_merge($turmultiplechoicesourcequestionids, $turprovesourcequestionids);
$destinationquestionids = array_merge($turmultiplechoicedestinationquestionids, $turprovedestinationquestionids);

if (count($sourcequestionids) != count($destinationquestionids)) {
    mtrace("Differing number of turmultiplechoice questions in the two specified courses. Not happy with this. Aborting.");
    die;
}

$fs = get_file_storage();

// Iterate through the array of question ids
foreach ($sourcequestionids as $key => $sourcequestionid) {

    // Clear any destination questionimage file areas for new question.
    $fs->delete_area_files(1, 'question', 'questionimage', $destinationquestionids[$key]);

    // Get source questionimage files.
    $questionimagefiles = $fs->get_area_files(1, 'question', 'questionimage', $sourcequestionid, 'id', false);

    // Create new destination questionimage files.
    foreach ($questionimagefiles as $questionimagefile) {
        $newquestionimagefile = new stdClass();
        $newquestionimagefile->itemid = $destinationquestionids[$key];
        $fs->create_file_from_storedfile($newquestionimagefile, $questionimagefile);
        mtrace('Copied question image file ' . $sourcequestionid);
    }

    // Clear any destination questionsound file areas for new question.
    $fs->delete_area_files(1, 'question', 'questionsound', $destinationquestionids[$key]);

    // Get source questionsound files.
    $questionsoundfiles = $fs->get_area_files(1, 'question', 'questionsound', $sourcequestionid, 'id', false);

    // Create new destination questionsound files.
    foreach ($questionsoundfiles as $questionsoundfile) {
        $newquestionsoundfile = new stdClass();
        $newquestionsoundfile->itemid = $destinationquestionids[$key];
        $fs->create_file_from_storedfile($newquestionsoundfile, $questionsoundfile);
        mtrace('Copied question sound file ' . $sourcequestionid);
    }

    // Clear any destination answersound file areas for new question.
    $fs->delete_area_files(1, 'question', 'answersound', $destinationquestionids[$key]);

    // Get source answersound files.
    $questionsoundfiles = $fs->get_area_files(1, 'question', 'answersound', $sourcequestionid, 'id', false);

    // Create new destination answersound files.
    foreach ($questionsoundfiles as $questionsoundfile) {
        $newquestionsoundfile = new stdClass();
        $newquestionsoundfile->itemid = $destinationquestionids[$key];
        $fs->create_file_from_storedfile($newquestionsoundfile, $questionsoundfile);
        mtrace('Copied question answersound file ' . $sourcequestionid);
    }

    // Clear any destination feedbacksound file areas for new question.
    $fs->delete_area_files(1, 'question', 'feedbacksound', $destinationquestionids[$key]);

    // Get source feedbacksound files.
    $questionsoundfiles = $fs->get_area_files(1, 'question', 'feedbacksound', $sourcequestionid, 'id', false);

    // Create new destination feedbacksound files.
    foreach ($questionsoundfiles as $questionsoundfile) {
        $newquestionsoundfile = new stdClass();
        $newquestionsoundfile->itemid = $destinationquestionids[$key];
        $fs->create_file_from_storedfile($newquestionsoundfile, $questionsoundfile);
        mtrace('Copied question feedbacksound file ' . $sourcequestionid);
    }

    $sourceanswerids = array_keys($DB->get_records_menu('question_answers', array('question' => $sourcequestionid)));
    $destinationanswerids = array_keys($DB->get_records_menu('question_answers', array('question' => $destinationquestionids[$key])));

    foreach ($sourceanswerids as $answerkey => $sourceanswerid) {

        // Clear any destination question answersound file areas for new question.
        $fs->delete_area_files(1, 'question', 'answersound', $destinationanswerids[$answerkey]);

        // Get source question answersound files.
        $questionanswersoundfiles = $fs->get_area_files(1, 'question', 'answersound', $sourceanswerid, 'id', false);

        // Create new destination question answersound files.
        foreach ($questionanswersoundfiles as $questionanswersoundfile) {
            $newquestionanswersoundfile = new stdClass();
            $newquestionanswersoundfile->itemid = $destinationanswerids[$answerkey];
            $fs->create_file_from_storedfile($newquestionanswersoundfile, $questionanswersoundfile);
            mtrace('Copied question answersound file ' . $sourceanswerid);
        }

        // Clear any destination question feedbacksound file areas for new question.
        $fs->delete_area_files(1, 'question', 'feedbacksound', $destinationanswerids[$answerkey]);

        // Get source question feedbacksound files.
        $questionfeedbacksoundfiles = $fs->get_area_files(1, 'question', 'feedbacksound', $sourceanswerid, 'id', false);

        // Create new destination question feedbacksound files.
        foreach ($questionfeedbacksoundfiles as $questionfeedbacksoundfile) {
            $newquestionfeedbacksoundfile = new stdClass();
            $newquestionfeedbacksoundfile->itemid = $destinationanswerids[$answerkey];
            $fs->create_file_from_storedfile($newquestionfeedbacksoundfile, $questionfeedbacksoundfile);
            mtrace('Copied question feedbacksound file ' . $sourceanswerid);
        }
    }
}

mtrace('Fin.');

/**
 * Identify all of a particular question type in the passed course.
 *
 * @param int $courseid Course Id
 * @param string $questiontype questiontype
 * @return array
 */
function question_ids($courseid, $questiontype) {
    global $DB;

    $sql = "SELECT qs.questionid
              FROM {quiz_slots} qs
              JOIN {quiz} q ON q.id = qs.quizid
              JOIN {question} qu ON qu.id = qs.questionid
             WHERE q.course = :course
               AND qu.qtype = :qtype";

    $params = array(
        'course' => $courseid,
        'qtype' => $questiontype
    );

    return array_keys($DB->get_records_sql_menu($sql, $params));
}
