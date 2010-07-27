<?php

/*********************************************************************************
 * Tidbit is a data generation tool for the SugarCRM application developed by
 * SugarCRM, Inc. Copyright (C) 2004-2010 SugarCRM Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/

if(!defined('sugarEntry'))define('sugarEntry', true);

chdir('..');

$recordsPerPage = 1000;
$relQueryCount = 0;

require_once('install_config.php');


if(!isset($_REQUEST['factor'])){
    if(isset($_REQUEST['loadFactor'])){
        $factor = $_REQUEST['loadFactor']/$modules['Accounts'];
        foreach($modules as $m=>$n){
            $modules[$m] *= $factor;
        }
        /* Load Factor doesn't affect users or teams, that gets set seperately. */
        $modules['Teams'] = $_REQUEST['UsersLF']*($modules['Teams']/$modules['Users']);
        $modules['Users'] = $_REQUEST['UsersLF'];
    }
}else{
    foreach($modules as $m=>$n){
        if(isset($_REQUEST[$m])){
            $modules[$m]=  $_REQUEST[$m];
        }
    }
}

session_start();
if(isset($_SESSION['modules'])){
    $modules = $_SESSION['modules'];
}
if(!isset($_SESSION['UseExistUsers'])){
	if(isset($_REQUEST['UseExistUsers'])){
        $_SESSION['UseExistUsers'] = $_REQUEST['UseExistUsers'];
    }else{
    	$_SESSION['UseExistUsers'] = false;
    }
}

$_SESSION['turbo'] = (!empty($_REQUEST['turbo']) || !empty($_SESSION['turbo']))? true: false;
$_SESSION['clean'] = (!empty($_REQUEST['clean']) || !empty($_SESSION['clean']))? true: false;
$_SESSION['obliterate'] = (!empty($_REQUEST['obliterate']) || !empty($_SESSION['obliterate']))? true: false;
$_SESSION['debug'] = (!empty($_REQUEST['debug']) || !empty($_SESSION['debug']))? true: false;


echo "<style type='text/css'>";
echo "BODY{background-color:black;color:white;}";
echo "</style>\n";

if(empty($_REQUEST['page'])){
    session_destroy();
    
    $totalRecordEstimate = array_sum($modules) - $modules['Users'] - $modules['Teams'];
    foreach($tidbit_relationships as $m => $rs){
        foreach($rs as $rm => $r){
            $recCount = empty($r['ratio'])?$modules[$rm]:$r['ratio']*$modules[$m];
            $totalRecordEstimate += $recCount;
        }
    }
    
    echo <<<EOS
    <script type='text/javascript'>
        var t=true;
        var toggle = function()
        {
            if(t)
            {
                t=false;
                document.getElementById("customBoxes").style.display = "inline";
                document.getElementById("stdLoad").style.display = "none";
            }else{
            	t=true;
                document.getElementById("customBoxes").style.display = "none";
                document.getElementById("stdLoad").style.display = "inline";
            }
        };
    </script>
EOS;
    
    echo "<script type='text/javascript'>var lf = {$modules['Accounts']};\n";
    echo "var updateStats = function(value){lf = value; document.getElementById(\"stats\").innerHTML = ";
    echo "\"This script will install records for ";
    echo "<b><span id='users'>\" + users + \"</span></b> Users, ";
    echo "<b><span id='teams'>\" + users*".($modules['Teams']/$modules['Users'])." + \"</span></b> Teams, ";
    foreach($modules as $m=>$n){
        if(($m != 'Users') && ($m != 'Teams')){
            echo "<b>\" + value*". $modules[$m]/$modules['Accounts'] ." + \"</b> $m, ";
        }
    }
    echo "and their mutual relationships into the database, yielding a total of ";
    echo "<b><span id='total'></span></b> records inserted.\"; updateTotal(value);";
    echo "document.getElementById(\"stats\").style.display = \"block\";};";
    echo "var updateTotal = function(){";
    echo "document.getElementById(\"total\").innerHTML = (lf*". $totalRecordEstimate/$modules['Accounts'] . "+users*".(1+($modules['Teams']/$modules['Users'])).");};";
    echo "var users = {$modules['Users']};\n";
    echo "var updateUsers = function(value){users = value;";
    echo "document.getElementById(\"users\").innerHTML = users;";
    echo "document.getElementById(\"teams\").innerHTML = users*".($modules['Teams']/$modules['Users']).";";
    echo "updateTotal();};";
    echo "</script>\n";
    
    echo "<form name='dataTool'><input type='hidden' name='page' value='1'><input type='hidden' name='offset' value='0'>";
    
    echo "<table><tr><th colspan='2'><input name='factor' value='custom' type='checkbox' onclick='toggle()' id='r2' /><label for='r2'>Use Custom Ratios</label></th><th>&nbsp;</th></tr>";
    
    echo "<tr><td style='vertical-align:top;'><div id='stdLoad'>";
    echo "<table><tr><td>Users</td><td><input name='UsersLF' value='{$modules['Users']}' onkeyup='updateUsers(this.value);' onblur='updateUsers(this.value);'></td></tr>";
    echo "<tr><td><label for='eu1'>Use Existing Users</label></td><td><input type='checkbox' name='UseExistUsers' id='eu1' /></td></tr>";
    echo "<tr><td colspan='2'><p style='font-size:small;font-style:italic;width:18em;'>If this box is checked, no users will be inserted into the database.";
    echo "  If <b>Clean Mode</b> is on, the existing users won't be erased.  The script will assume that the number of users in the field above is the number of existing";
    echo " users in your system.<br /><br /></p></td></tr>";
    echo "<tr><td>Loading Factor</td><td><input name='loadFactor' value='{$modules['Accounts']}' onkeyup='updateStats(this.value);' onblur='updateStats(this.value);'></td></tr>";
    echo "<tr><td colspan='2'><p style='font-size:small;font-style:italic;width:18em;'>Use this field to specify the number of Accounts to be created.";
    echo "  A realistic number of elements will be chosen for the other fields.</p>";
    //echo "<p style='font-size:small;font-style:italic;width:18em;'>If you use custom values, be aware that some element relationships will be faulty.</p>";
    echo "<p style='font-size:small;font-style:italic;width:18em;' id='stats'>This script will install records for ";
    echo "<b><span id='users'>{$modules['Users']}</span></b> Users, ";
    echo "<b><span id='teams'>{$modules['Teams']}</span></b> Teams, ";
    foreach($modules as $m=>$n){
        echo "<b>". $modules[$m] ."</b> $m, ";
    }
    echo "and their mutual relationships into the database, yielding a total of ";
    echo "<b><span id='total'>$totalRecordEstimate</span></b> records inserted.</p></td></tr></table>";
    echo "</div></td><td style='vertical-align:top;'><div id='customBoxes' style='display:none;'><table>";

    foreach($modules as $m=>$n){
        echo "<tr><td>$m</td><td><input name='$m' value='$n'></td></tr>";
    }
    
    echo "</table></div></td>\n<td style='vertical-align:top;'><table>";
    
    echo "<tr><td><label for='cb1'><b>Turbo Mode</b></label></td><td><input name='turbo' value='1' type='checkbox' id='cb1'></td></tr>";
    echo "<tr><td colspan='2'><p style='font-size:small;font-style:italic;width:18em;'>In <b>turbo mode</b>, groups of 1000 duplicates will be inserted.  ";
    echo "This allows you to quickly generate a large volume of data.<br /><br /></p></td></tr>";
    echo "<tr><td><label for='cb2'><b>Obliterate Mode</b></label></td><td><input name='obliterate' value='1' type='checkbox' id='cb2'></td></tr>";
    echo "<tr><td colspan='2'><p style='font-size:small;font-style:italic;width:18em;'>In <b>obliterate mode</b>, every table into which seed data or ";
    echo "seed data relationships will be inserted will be emptied.  This includes data created by real users.<br /><br /></p></td></tr>";
    echo "<tr><td><label for='cb3'><b>Clean Mode</b></label></td><td><input name='clean' value='1' type='checkbox' id='cb3'></td></tr>";
    echo "<tr><td colspan='2'><p style='font-size:small;font-style:italic;width:18em;'>In <b>clean mode</b>, all seed data ";
    echo "and seed data relationships will be erased.  Any data created or modified through the application will remain untouched. ";
    echo "Has no effect if <b>obliterate mode</b> is enabled. <br /><br /></p></td></tr>";
    echo "<tr><td><label for='cb4'><b>Debug Mode</b></label></td><td><input name='debug' value='1' type='checkbox' id='cb4'></td></tr>";
    echo "<tr><td colspan='2'><p style='font-size:small;font-style:italic;width:18em;'>With <b>debug mode</b> on, all insertion and transactional queries ";
    echo "will be logged to the file <b>executedQueries.txt</b> in the Tidbit directory. <br /><br /></p></td></tr>";

    
    echo "</table></td></tr>";
    
    echo "</table><input type='submit' value='Install'></form>";
}else{
    require_once('include/utils.php');
    $page = $_REQUEST['page'];
    $offset = $_REQUEST['offset'];

    if($page == 1 && $offset == 0){
        $_SESSION['modules'] = $modules;
        $_SESSION['startTime'] = microtime();
        $_SESSION['baseTime'] = time();
        $_SESSION['totalRecords'] = 0;
        foreach($modules as $records){
            $_SESSION['totalRecords'] += $records;
        }
        $_SESSION['processedRecords'] = 0;
        $_SESSION['allProcessedRecords'] = 0;
        if($_SESSION['debug'])
        {
            $GLOBALS['queryFP'] = fopen('Tidbit/executedQueries.txt', 'w');
        }
    }elseif($_SESSION['debug']){
        $GLOBALS['queryFP'] = fopen('Tidbit/executedQueries.txt', 'a');
    }


    require_once('include/utils/progress_bar_utils.php');
    
    /* Start output buffering so progress bar utils doesn't blow up in php 4.3.0/4.3.1 */
    ob_start();

    $module_keys = array_keys($modules);
    display_progress_bar('modules_progress', $_SESSION['processedRecords'], $_SESSION['totalRecords']);
    echo "Total Time: " . microtime_diff($_SESSION['startTime'], microtime()) . "<br />\n";
    if($page > count($module_keys)){
        echo "Core Records Inserted: ".$_SESSION['processedRecords']."<br />\n";
        echo "Total Records Inserted: ".$_SESSION['allProcessedRecords']."<br />\n";
        die('Done <a href="install.php">[More]</a>');
    }
    $module = $module_keys[$page - 1];
    
    if((($module == 'Users') || ($module == 'Teams')) && $_SESSION['UseExistUsers']){
        /* If UseExistUsers is set, just move to the next module */
        $page++;
        echo "<script>document.location.href='install.php?page=$page&offset=0';</script>";
    }

    $total = $modules[$module];
    $max = min($recordsPerPage + $offset, $total );
    echo $module . ' [' . $offset . '-' . $max . ' of ' . $total . ']';
    display_progress_bar('module_progress', $offset ,$total);

    
    set_time_limit(3600);
    
    require_once('config.php');
    require_once('include/modules.php');
    
    $GLOBALS['relatedQueries'] = array();
    $GLOBALS['queries'] = array();
    $GLOBALS['relatedQueriesCount'] = 0;
    
    require_once('include/database/PearDatabase.php');
    require_once('log4php/LoggerManager.php');
    require_once('Tidbit/Data/DefaultData.php');
    require_once('Tidbit/DataTool.php');
    require_once('Tidbit/install_functions.php');
    require_once('Tidbit/Data/contactSeedData.php');
    
    $app_list_strings = return_app_list_strings_language('en_us');
    $GLOBALS['log']= LoggerManager::getLogger('Tidbit');
    $GLOBALS['db'] = PearDatabase::getInstance();
    startTransaction();

    $class = $beanList[$module];
    require_once($beanFiles[$class]);
    $bean = new $class();
    
    if($_SESSION['obliterate'] && $offset == 0){
        print('OBLITERATING ALL DATA ... SWEEP SWEEP SWEEP...');
        /* Make sure not to delete the admin! */
        if($module == 'Users'){
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE id != '1'");
        }else{
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE 1=1");
        }
        if(!empty($tidbit_relationships[$module])){
            foreach($tidbit_relationships[$module] as $rel){
                $GLOBALS['db']->query("DELETE FROM {$rel['table']} WHERE 1=1");
            }
        }
    }elseif($_SESSION['clean'] && $offset == 0){
        print('CLEANING OLD DATA ... SWEEP SWEEP SWEEP...');
        /* Make sure not to delete the admin! */
        if($module == 'Users'){
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE id != '1' AND id LIKE 'seed-%'");
        }else{
            $GLOBALS['db']->query("DELETE FROM $bean->table_name WHERE 1 = 1 AND id LIKE 'seed-%'");
        }
        if(!empty($tidbit_relationships[$module])){
            foreach($tidbit_relationships[$module] as $rel){
                $GLOBALS['db']->query("DELETE FROM {$rel['table']} WHERE 1 = 1 AND id LIKE 'seed-%'");
            }
        }
    }
    if(file_exists('Tidbit/Data/' . $bean->module_dir . '.php')){
        require_once('Tidbit/Data/' . $bean->module_dir . '.php');
    }
    
    // Only open the relationships cache for the tables this module works with?
//    if(file_exists('relationships.txt')){
//        //echo file_get_contents('relationships.txt');
//        eval('$relTable = ' . file_get_contents('relationships.txt') . ';');
//        //var_dump($relTable);
//    }
//    $rfp = fopen('relationships.txt', 'w');

    /* We need to insert min($recordsPerPage, ($total - $offset)) records
     * into the DB.  We are using the module and table-name given by
     * $module and $bean->table_name. */
    $ibfd = new DataTool();
    $ibfd->fields = $bean->field_defs;
    $ibfd->table_name = $bean->table_name;
    $ibfd->module = $module;
    
    for($i = $offset; $i < $total && $i < $offset + $recordsPerPage ; $i++){
        $ibfd->count = $i;
        /* Don't turbo Users or Teams */
        if(!$_SESSION['turbo']  || $i == $offset || ($module != 'Users') || ($module != 'Teams')){
            $ibfd->clean();
            $ibfd->count = $i;
            $ibfd->generateData();
        }
        $ibfd->generateId();
        $ibfd->createInserts();
        $ibfd->generateRelationships();
        
        $_SESSION['processedRecords']++;
        
        if($i%10 == 0){
            update_progress_bar('modules_progress', $_SESSION['processedRecords'], $_SESSION['totalRecords']);   
            update_progress_bar('module_progress', $i , $total);
        }
        
        //flush the relatedQueries every 2000, and at the end of each page.
        if(($relQueryCount >= 2000) || ($i == $total-1) || ($i == $offset + $recordsPerPage - 1)){
            echo '.';
            foreach($GLOBALS['relatedQueries'] as $data){
                $head = $data['head'];
                unset($data['head']);
                processQueries($head, $data);
            }
            $GLOBALS['relatedQueries'] = array();
            $relQueryCount = 0;
        }
    }
    
    /* Use our query wrapper that makes things fast. */
    processQueries($GLOBALS['queryHead'], $GLOBALS['queries']);
   
    /* Clear queries */
    unset($GLOBALS['queryHead']);
    unset($GLOBALS['queries']);
  
    endTransaction();
    
    if(!empty($GLOBALS['queryFP']))
    {
        fclose($GLOBALS['queryFP']);
    }
    
//    if(($relTable) && !empty($relTable)){
//        fwrite($rfp, var_export($relTable, true));
//        fclose($rfp);
//    }
//    else{
//    	fclose($rfp);
//        unlink('relationships.txt');
//    }
    
    /* Send browser to the next page */
    if($i < $total){
        echo "<script>document.location.href='install.php?page=$page&offset=$i';</script>";
    }else{
        $page++;
        echo "<script>document.location.href='install.php?page=$page&offset=0';</script>";
    }

    ob_end_flush();
}





?>
