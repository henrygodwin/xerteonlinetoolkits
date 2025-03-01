<?php
/**
 * Licensed to The Apereo Foundation under one or more contributor license
 * agreements. See the NOTICE file distributed with this work for
 * additional information regarding copyright ownership.

 * The Apereo Foundation licenses this file to you under the Apache License,
 * Version 2.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.

 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
 
/**
 *
 * Edit page, brings up the xerte editor window
 *
 * @author Patrick Lockley
 * @version 1.0
 */

require_once(dirname(__FILE__) . "/config.php");

require $xerte_toolkits_site->php_library_path . "screen_size_library.php";
require $xerte_toolkits_site->php_library_path . "template_status.php";
require $xerte_toolkits_site->php_library_path . "display_library.php";
require $xerte_toolkits_site->php_library_path . "user_library.php";

/**
 * Function update_access_time
 * This function updates the time a template was last edited
 * @param array $row_edit = an array returned from a mysql query
 * @return bool True or False if two params match
 * @version 1.0
 * @author Patrick Lockley
 */
function update_access_time($row_edit){
    global $xerte_toolkits_site;
    /* This function is called even if the template is new - in which case it fails as a record doesn't exist */
    db_query("UPDATE {$xerte_toolkits_site->database_table_prefix}templatedetails SET date_accessed=? WHERE template_id = ?", array(date('Y-m-d H:i:s'), $row_edit['template_id']));
    return true;
}


/*
 * Check the template ID is numeric
 */

if(!isset($_GET['template_id']) || !is_numeric($_GET['template_id'])) {
    _debug("Template id is not numeric. ->" . $_GET['template_id']);
    dont_show_template();
    exit(0);
}

/*
 * Find out if this user has rights to the template
 */

$safe_template_id = (int) $_GET['template_id'];

$query_for_edit_content_strip = str_replace("\" . \$xerte_toolkits_site->database_table_prefix . \"", $xerte_toolkits_site->database_table_prefix, $xerte_toolkits_site->play_edit_preview_query);

$query_for_edit_content = str_replace("TEMPLATE_ID_TO_REPLACE", $safe_template_id, $query_for_edit_content_strip);

$row_edit = db_query_one($query_for_edit_content);

if(empty($row_edit)) {
    die("Invalid template_id (could not find in DB) (1)");
}

if(isset($_SESSION['toolkits_logon_id'])){

	if(has_rights_to_this_template($safe_template_id,$_SESSION['toolkits_logon_id'])){

		// Check if user is editor (could be read only)

		if(is_user_an_editor($safe_template_id,$_SESSION['toolkits_logon_id'])){

			// Check for multiple editors
			if(has_template_multiple_editors($safe_template_id)){
				// Check for lock file. A lock file is created to prevent more than one editor editing the same template at the same time
				if(file_exists($xerte_toolkits_site->users_file_area_full . $row_edit['template_id'] . "-" . $row_edit['username'] . "-" . $row_edit['template_name'] . "/lockfile.txt")){

					// Lock file exists, so open it up and see who created it
					$lock_file_data = file_get_contents($xerte_toolkits_site->users_file_area_full . $row_edit['template_id'] . "-" . $row_edit['username'] . "-" . $row_edit['template_name'] . "/lockfile.txt");

					$temp = explode("*", $lock_file_data);

					$lock_file_creator = $temp[0];

					// get username only
					$temp = explode(" ", $lock_file_creator);
					$lock_file_creator_username = $temp[0];

					/*
					 * Check if lock file creator is current user, if so, continue into the code
					 */

					if($lock_file_creator_username==$_SESSION['toolkits_logon_username']) {
						if(update_access_time($row_edit)) {
							// Display the editor
							require $xerte_toolkits_site->root_file_path . "modules/" . $row_edit['template_framework'] . "/edit.php";
							output_editor_code($row_edit, $xerte_toolkits_site, "true", true);
						}
						else {
							// show error
							error_show_template();
							exit(0);
						}
					}
					else {
						if(isset($_POST['lockfile_clear'])) {

							/*
							 * Modify the lockfile to just the user entry
							 */

							$file_handle = fopen($xerte_toolkits_site->users_file_area_full . $row_edit['template_id'] . "-" . $row_edit['username'] . "-" . $row_edit['template_name'] . "/lockfile.txt", 'w');

							fwrite($file_handle, $_SESSION['toolkits_logon_username'] . " (" . date("Y-m-d H:i:s") . ")*");

							fclose($file_handle);

							/*
							 * Update the time this template was last edited
							 */

							if(update_access_time($row_edit)){

								require $xerte_toolkits_site->root_file_path . "modules/" . $row_edit['template_framework'] . "/edit.php";
								output_editor_code($row_edit, $xerte_toolkits_site, "true", true);
							}
							else {
								error_show_template();
								exit(0);
							}
						}
						else {

							// Update the lock file. The lock file format is: creator_id (date/time)*[user_id (date/time),...]
							// where 'user_id' are users who tried to edit the template when it was already being edited

							$new_lock_file = $lock_file_data . $_SESSION['toolkits_logon_username'] . " (" . date("Y-m-d H:i:s") . "),";
							$file_handle = fopen($xerte_toolkits_site->users_file_area_full . $row_edit['template_id'] . "-" . $row_edit['username'] . "-" . $row_edit['template_name'] . "/lockfile.txt",'w');
							fwrite($file_handle, $new_lock_file);
							fclose($file_handle);
							output_locked_file_code($lock_file_creator_username);
						}
					}
				}
				else {

					// No lock file, so create one
					$file_handle = fopen($xerte_toolkits_site->users_file_area_full . $row_edit['template_id'] . "-" . $row_edit['username'] . "-" . $row_edit['template_name'] . "/lockfile.txt", 'w');
					fwrite($file_handle, $_SESSION['toolkits_logon_username'] . " (" . date("Y-m-d H:i:s") . ")*");
					fclose($file_handle);

					// Update the time this template was last edited
					if(update_access_time($row_edit)){
						require $xerte_toolkits_site->root_file_path . "modules/" . $row_edit['template_framework'] . "/edit.php";
						output_editor_code($row_edit, $xerte_toolkits_site, "true", true);
					}else{
						error_show_template();
						exit(0);
					}

				}

			}
			else {
				// One editor (but shared) for this project, so continue without creating a lock file
				if(update_access_time($row_edit)){
					require $xerte_toolkits_site->root_file_path . "modules/" . $row_edit['template_framework'] . "/edit.php";
					output_editor_code($row_edit, $xerte_toolkits_site, "true", false);
				}
				else {
					error_show_template();
					exit(0);
				}
			}
		}
		else {
			// One editor (and no sharing) for this project, so continue without creating a lock file
			if(update_access_time($row_edit)){
				_debug("editphp - no sharing etc");
				require $xerte_toolkits_site->root_file_path . "modules/" . $row_edit['template_framework'] . "/edit.php";
				output_editor_code($row_edit, $xerte_toolkits_site, "false", false);
			}
			else {
				error_show_template();
				exit(0);
			}
		}
	}
	else if(is_user_permitted("projectadmin")) {
		// Is the current user an administrator - If so access here.
		require $xerte_toolkits_site->root_file_path . "modules/" . $row_edit['template_framework'] . "/edit.php";
		output_editor_code($row_edit, $xerte_toolkits_site, "false", false);
	}
	else {
		// Wiki mode - check to see if template allows anonymous editing.

		$string_for_flash_xml = $xerte_toolkits_site->users_file_area_short . $row_edit['template_id'] . "-" . $row_edit['username'] . "-" . $row_edit['template_name'] . "/data.xml";
		$buffer = file_get_contents($string_for_flash_xml);
		if(strpos($buffer,"editable=true")==false){
			// so the user sees a blank page?
		}else{
			// Wiki mode set
			require $xerte_toolkits_site->root_file_path . "modules/" . $row_edit['template_framework'] . "/edit.php";
			output_editor_code($row_edit, $xerte_toolkits_site, "true", false);
		}
	}

}else{
	die("Session ID not set");
}
