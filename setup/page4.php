<?php
/**
 * Licensed to The Apereo Foundation under one or more contributor license
 * agreements. See the NOTICE file distributed with this work for
 * additional information regarding copyright ownership.
 *
 * The Apereo Foundation licenses this file to you under the Apache License,
 * Version 2.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
global $xerte_toolkits_site;
global $development;
$xerte_toolkits_site = new stdClass();

require_once(dirname(__FILE__) . "/../database.php");

$success_string = '';
$fail_string = '';
if (function_exists('get_magic_quotes_gpc')) {
    $magic_quotes = get_magic_quotes_gpc();
}
else {
    $magic_quotes = false;
}
$development = true;

/*
function _debug($string) {
    // pass, for now.
}
*/

ini_set('error_reporting', E_ALL);

require_once('page_header.php');

$res = db_query("SELECT * FROM {$xerte_toolkits_site->database_table_prefix}sitedetails");

if(isset($res[0]['site_id'])){

die("<p>You appear to have already created a database and so do not need to install again</p>");

}

$res = db_query("DELETE FROM {$xerte_toolkits_site->database_table_prefix}sitedetails");
if($res === false) {
    die("Error running SQL query");
}

$res = db_query("insert  into {$xerte_toolkits_site->database_table_prefix}sitedetails(site_id) VALUES (1)");
if($res === false) {
    die("Error running SQL query");
}

if(!empty($_POST['site_url'])) {
    if(!preg_match('/^http/', $_POST['site_url'])) {
        $_POST['site_url'] = 'http://' . $_POST['site_url'];
    }
}
foreach(array('news_text', 'pod_one', 'pod_two', 'form_string', 'peer_form_string', 'play_edit_preview_query') as $key) {
    $_POST[$key] = base64_encode(stripcslashes($_POST[$key])); 
}

foreach(array('site_url', 'apache', 'enable_mime_check', 'mimetypes', 'enable_file_ext_check', 'file_extensions', 'enable_clamav_check', 'clamav_opts', 'LDAP_preference', 'LDAP_filter',
    'integration_config_path', 'admin_username', 'admin_password', 'site_session_name', 'site_title', 'site_name', 'site_logo', 'organisational_logo','welcome_message', 'site_text', 'news_text', 'pod_one', 'pod_two',
    'copyright', 'rss_title', 'synd_publisher', 'synd_rights', 'synd_license', 'demonstration_page', 'form_string', 'peer_form_string', 'module_path', 'website_code_path', 'users_file_area_short',
    'php_library_path', 'error_log_path', 'email_error_list', 'error_log_message', 'max_error_size', 'max_error_size', 'error_email_message', 'authentication_method',
    'ldap_host', 'ldap_port', 'bind_pwd', 'basedn', 'bind_dn', 'flash_save_path', 'flash_upload_path', 'flash_preview_check_path', 'flash_flv_skin',
    'site_email_account', 'headers', 'email_to_add_to_username', 'proxy1', 'port1', 'feedback_list', 'play_edit_preview_query', 'LRS_Endpoint', 'LRS_Key', 'LRS_Secret') as $field) {

    #usecase for admin password
    if($field=='admin_password') {
        $res = db_query("UPDATE {$xerte_toolkits_site->database_table_prefix}sitedetails SET $field = ? WHERE site_id = ?", array(hash('sha256', $_POST[$field]), '1'));
    } else {
        $res = db_query("UPDATE {$xerte_toolkits_site->database_table_prefix}sitedetails SET $field = ? WHERE site_id = ?", array($_POST[$field], '1'));
    }
    if ($res === false) {
        $fail_string .= "<div style='color: red;'>The sitedetails {$field} query has failed.</div><br/>";
    } else {
        $success_string .= "The sitedetails {$field} query succeeded<br/>";
    }
}

$ldap_fields = array('ldap_filter' => 'LDAP_filter', 'ldap_filter_attr' => 'LDAP_preference', 'ldap_knownname' => 'ldap_host', 'ldap_host' => 'ldap_host', 'ldap_port' => 'ldap_port', 
                     'ldap_password' => 'bind_pwd', 'ldap_basedn' => 'basedn', 'ldap_username' => 'bind_dn');
$comma = '';
$query = "INSERT INTO {$xerte_toolkits_site->database_table_prefix}ldap (" . implode(',', array_keys($ldap_fields)) . ") VALUES (";
$values = array();
foreach($ldap_fields as $post_key) {
    $query .= $comma;
    $query .= "?";
    $comma = ",";
    $values[] = $_POST[$post_key];
}
$query .= ")";

$res = db_query($query, $values);
if($res===false) {
    $fail_string .= "The ldap query has failed (query: {{{$query}}})<br/>";
}
else {
    $success_string .= "The 'ldap' insert query has succeeded<br/>";
}


if(!$magic_quotes){
    $import_path = addslashes(x_clean_input($_POST['import_path']));
}else{
    $import_path = x_clean_input($_POST['import_path']);
}

// Check whether $import_path is a path
if (!is_dir($import_path)) {
    $fail_string .= "The import path is not a valid directory<br>";
}

$query = "update " . $xerte_toolkits_site->database_table_prefix . "sitedetails set import_path=? where site_id=\"1\"";

$query_response = db_query($query, array(str_replace("\\\\","/",$import_path)));

if($query_response === false){
    $fail_string .= "The sitedetails import_path query " . $query . " has failed.<br>";
}else{
    $success_string .= "The sitedetails import_path query succeeded <br>";
}

if(!$magic_quotes){
    $root_path = x_clean_input($_POST['root_file_path']);
}else{
    $root_path = x_clean_input($_POST['root_file_path']);

}

// Check whether $root_path is a path
if (!is_dir($root_path)) {
    $fail_string .= "The root path is not a valid directory<br>";
}

$query = "update " . $xerte_toolkits_site->database_table_prefix . "sitedetails set root_file_path=? where site_id=\"1\"";
$query_response = db_query($query, array(str_replace("\\\\","/",$root_path)));

if($query_response === false){
    $fail_string .= "The sitedetails root_file_path query " . $query . " has failed.<br>";
}else{
    $success_string .= "The sitedetails root_file_path query succeeded <br>";

}

if(!$magic_quotes){
    $clamav_path = addslashes(x_clean_input($_POST['clamav_cmd']));
}else{
    $clamav_path = x_clean_input($_POST['clamav_cmd']);
}

$query = "update " . $xerte_toolkits_site->database_table_prefix . "sitedetails set clamav_cmd=? where site_id=\"1\"";
$query_response = db_query($query, array(str_replace("\\\\","/",$clamav_path)));

if($query_response === false){
    $fail_string .= "The sitedetails clamav_cmd query " . $query . " has failed.<br>";
}else{
    $success_string .= "The sitedetails clamav_cmd query succeeded <br>";
}


// Setup .htaccess file if we can...
if($_POST['apache']=="true"){
    $replace = substr($_SERVER['PHP_SELF'],0,strpos($_SERVER['PHP_SELF'],"/",1));
    $buffer = file_get_contents("htaccess.conf");
    $buffer = str_replace("*/",$replace . "/",$buffer);
    $file_handle = fopen(".htaccess",'w');
    fwrite($file_handle,$buffer,strlen($buffer));
    fclose($file_handle);
    if(chmod(".htaccess",0744) && rename(".htaccess","../.htaccess") && chmod("../.htaccess",0744)) {
        $success_string .= "<p>.htaccess setup succeeded</p>";
    }
}

?>

<h2>Install complete</h2>

<?php

if($fail_string!=""){

    echo "<p><b?The following queries failed</b> - <br /> " . $fail_string . "</p>";
    echo "<p>These failures may affect your site, please see if they can be rectified using the management tools or altering the database directly.</p>";

}

if($success_string!=""){

    echo "<p>The following queries suceeded - <br /> " . $success_string . "</p>";

}

?>
<p> Your site URL is  <a href="http://<?php echo x_clean_input($_SERVER['HTTP_HOST']) . substr(x_clean_input($_SERVER['PHP_SELF']),0,strlen(x_clean_input($_SERVER['PHP_SELF']))-15); ?>"><?php echo x_clean_input($_SERVER['HTTP_HOST']) . substr(x_clean_input($_SERVER['PHP_SELF']),0,strlen(x_clean_input($_SERVER['PHP_SELF']))-15); ?></a> </p>

<h2>Security Warning</h2>
<p><strong><u>If you have installed this on a public facing server, ensure you delete the following:<br/>
<ul>
    <li>/setup (this installer; it can be used to overwrite files on your webserver)</li>
</ul>
<p>You should also delete all of the following you are not planning to use:</p>
<ul>
    <li>webctlink,php (allows anyone to specify whatever username they wish)</li>
</ul>
</u>
</strong>
</p>

<h2>Register!</h2>
<p>Please register your site to receive valuable notifications regarding Xerte Online Toolkits. You can find the registration button in the management page:
    <a href="http://<?php echo x_clean_input($_SERVER['HTTP_HOST']) . substr(x_clean_input($_SERVER['PHP_SELF']),0,strlen(x_clean_input($_SERVER['PHP_SELF']))-15) . "management.php?register"; ?>"><?php echo $_SERVER['HTTP_HOST'] . substr($_SERVER['PHP_SELF'],0,strlen($_SERVER['PHP_SELF'])-15) . "management.php"; ?></a></p>

<h2>Need more help?</h2>
<p>Please see the Xerte Community site at <a href="http://www.xerte.org.uk" target="new">http://www.xerte.org.uk</a> and please consider joining the forum.</p>

<?php require_once('page_footer.php'); ?>
