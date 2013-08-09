<?php

require_once dirname(__FILE__) . '/inc.php';

$courseid = optional_param('courseid', 0, PARAM_INT);

require_login($courseid);

block_exaport_setup_default_categories();

$url = '/blocks/exaport/category.php?courseid='.$courseid;
$PAGE->set_url($url);

if (optional_param('action', '', PARAM_ALPHA) == 'delete') {
	$id = optional_param('id', 0, PARAM_INT);
	
	$category = $DB->get_record("block_exaportcate", array('id'=>$id));
	if (!$category) die(todo_string('category_not_found'));
	
	if (optional_param('confirm', 0, PARAM_INT)) {
		if (!$DB->delete_records('block_exaportcate', array('id'=>$id,"userid"=>$USER->id)))
		{
			$message = "Could not delete your record";
		}
		else
		{
			$conditions = array("categoryid" => $id);
			if ($entries = $DB->get_records_select('block_exaportitem', null, $conditions, '', 'id')) {
				foreach ($entries as $entry) {
					$DB->delete_records('block_exaportitemshar', array('itemid'=>$entry->id));
				}
			}
			$DB->delete_records('block_exaportitem', array('categoryid'=>$id));
			
			add_to_log($courseid, "bookmark", "delete category", "", $category->id);
			
			redirect('view_items.php?courseid='.$courseid.'&categoryid='.$category->pid);
		}
	}

	$optionsyes = array('action'=>'delete', 'courseid' => $courseid, 'confirm'=>1, 'sesskey'=>sesskey(), 'id'=>$id);
	$optionsno = array('courseid'=>$courseid, 'categoryid'=>$category->pid);
	
	$strbookmarks = get_string("mybookmarks", "block_exaport");
	$strcat = get_string("categories", "block_exaport");

	block_exaport_print_header("bookmarks");
	
	echo '<br />';
	echo $OUTPUT->confirm(get_string("deletecategroyconfirm", "block_exaport"), new moodle_url('category.php', $optionsyes), new moodle_url('view_items.php', $optionsno));
	
	$OUTPUT->footer();

	exit;
}


require_once("$CFG->libdir/formslib.php");
 
class simplehtml_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore! 
 
        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'pid');
        $mform->addElement('hidden', 'courseid');

        $mform->addElement('text', 'name', get_string('name'));
        $mform->addRule('name', todo_string('not empty'), 'required', null, 'client');

        $this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

//Instantiate simplehtml_form 
$mform = new simplehtml_form();

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
	redirect('view_items.php?courseid='.$courseid.'&categoryid='.optional_param('pid', 0, PARAM_INT));
} else if ($newEntry = $mform->get_data()) {
	$newEntry->userid = $USER->id;
	
	if ($newEntry->id) {
		$DB->update_record("block_exaportcate", $newEntry);
	} else {
		$DB->insert_record("block_exaportcate", $newEntry);
	}

	redirect('view_items.php?courseid='.$courseid.'&categoryid='.$newEntry->pid);
} else {
	block_exaport_print_header("bookmarks");
	
	$category = null;
	if ($id = optional_param('id', 0, PARAM_INT)) {
		$category = $DB->get_record_sql('
			SELECT c.id, c.name, c.pid
			FROM {block_exaportcate} c
			WHERE c.userid = ? AND id = ?
		', array($USER->id, $id));
	}
	if (!$category) $category = new stdClass;
	
	$category->courseid = $courseid;
	if (empty($category->pid)) $category->pid = optional_param('pid', 0, PARAM_INT);

	$mform->set_data($category);
	$mform->display();
  
	echo $OUTPUT->footer();
}
