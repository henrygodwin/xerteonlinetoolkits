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
require_once("../../../config.php");

_load_language_file("/website_code/php/management/user_templates.inc");
_load_language_file("/management.inc");

require("../user_library.php");
require("../url_library.php");
require("management_library.php");

if(is_user_permitted("projectadmin")){
	
	echo "<h2>" . MANAGEMENT_MENUBAR_TEMPLATES . "</h2>";
	echo "<div class=\"admin_block\">";
	
    $database_id = database_connect("templates list connected","template list failed");

    $query="select * from " . $xerte_toolkits_site->database_table_prefix . "logindetails order by surname,firstname,username" ;

    $logins = db_query($query);
   
    echo "<form name=\"user_templates\" action=\"javascript:list_templates_for_user('list_user')\">";

    echo "<input type=\"textinput\" name=\"searchfilter\" id=\"searchfilter\" /><button type=\"button\" class=\"xerte_button\" onclick=\"javascript:search_user_templates('searchfilter')\">" . USERS_MANAGEMENT_TEMPLATE_SEARCH . "</button><br/>";

    echo "<select id=\"list_user\">";
    foreach($logins as $row_users){
        echo "<option value=\"" . $row_users['login_id'] . "\">" . $row_users['surname'] . ", " . $row_users['firstname'] . " (" . $row_users['username'] . ")</option>";
    }

    echo "</select>";

    //}

    echo "<button type=\"submit\" class=\"xerte_button\">" . USERS_MANAGEMENT_TEMPLATE_VIEW . "</button> <button type=\"button\" class=\"xerte_button\" onclick=\"javascript:transfer_user_templates('list_user')\">" . USERS_MANAGEMENT_TEMPLATE_TRANSFER . "</button> </form>";

    echo "<div id=\"transferownership\" style=\"display:none;\"></div>";
    echo "<div id=\"usertemplatelist\"></div>";
    echo "</div>";

}else{

    management_fail();

}
