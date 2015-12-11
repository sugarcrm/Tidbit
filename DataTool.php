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

require_once('include/utils/db_utils.php');
/**
 * DataTool randomly generates data to be inserted into the Sugar application
 * A DataTool object corresponds to a Sugar module.  
 * install_cli.php creates a DataTool object for each Sugar module and
 * initializes its fields based on values from that Sugar module.
 *
 */
class DataTool{

    var $installData = array();
    var $fields = array();
    var $table_name = '';
    var $module = '';
    var $count = 0;
    // TeamSet with all teams inside
    static public $max_team_set_id = null;
    static $team_sets_array = array();
    static $relmodule_index = 0;

    // Cache for generateSeed function
    static $seedModules = array();
    static $seedFields = array();

    // based on xhprof, db_convert for datetime and time generation, consume a lot of time.
    // getConvertDatetime() function re-generate time only when this index reach max number
    // so we will re-generate time only for self::$datetimeIndexMax record
    static $datetimeCacheIndex = 0;
    static $datetimeIndexMax = 1000;

    // Skip db_convert for those types for optimization
    // TODO: move this to config
    static $notConvertedTypes = array(
        'int',
        'uint',
        'double',
        'float',
        'decimal',
        'decimal2',
        'short',
        'varchar',
        'text',
        'enum',
        'bool',
        'phone',
        'email',
        'created_by'
    );

    /**
     * Generate data and store it in the installData array.
     * This function calls generateSeed and passes the return
     * value as an argument to getData.  This is done for each
     * field.
     */
    function generateData(){
        /* For each of the fields in this record, we want to generate 
         * one element of seed data for it.*/
        foreach($this->fields as $field => $data){
            if(!empty($data['source']))continue;
            $type = (!empty($data['dbType']))?$data['dbType']:$data['type'];
            $GLOBALS['fieldData'] = $data;
            
            /* There are 3 unique parts to the seed: the Module name,
             * the count of the record, and the name of the field.
             * Using these 3 things should keep our seed unique enough.
             */
            $seed = $this->generateSeed($this->module, $field, $this->count);
            $value = $this->getData($field, $type, $data['type'], $seed);
            if(!empty($value) || $value == '0'){
                $this->installData[$field] = $value;
            }
        }
        
        /* These fields are filled in once per record. */
        if (!empty($this->fields['deleted'])) {
        	$this->installData['deleted'] = 0;	
        }
        if (!empty($this->fields['date_modified'])) {
        	$this->installData['date_modified'] = $this->getConvertDatetime();
        }
        if(!empty($this->fields['date_entered'])) {
        	$this->installData['date_entered'] = $this->installData['date_modified'];
        }
        if(!empty($this->fields['assign_user_id'])){
           $this->installData['assigned_user_id'] = 1;
        }
        if (!empty($this->fields['modified_user_id'])) {
        	$this->installData['modified_user_id'] = 1;	
        }
    	if(!empty($this->fields['team_id'])){
    		$teams = self::$team_sets_array[$this->installData['team_set_id']];
    		$index = count($teams) == 1 ? 0 : rand(0, count($teams)-1);
    		if(empty($this->fields['team_id']['source'])){
            	$this->installData['team_id'] = "'".$teams[$index]."'";
    		}
            //check if the assigned user is part of the team set, if not then add their default team.
            if(isset($this->installData['assigned_user_id'])){
                 $this->installData['team_set_id'] = add_team_to_team_set($this->installData['team_set_id'], $this->installData['assigned_user_id']);
            }
            $this->installData['team_set_id'] = "'".$this->installData['team_set_id']."'";

            //check if TbACLs is enabled
            if (!empty($_SESSION['tba'])) {
                $this->installData['team_set_selected_id'] = $this->installData['team_set_id'];
            }
        } 
    }
    
    /**
     * Generate a unique ID based on the module name, system time, and count (defined
     * in install_config.php for each module), and save the ID in the installData array.
     */
    function generateId() {
        $currentModule = $this->getAlias($this->module);
        $this->installData['id'] = $this->assembleId($currentModule, $this->count);

        if (strlen($this->installData['id']) > 36) {
            $moduleLength = strlen($currentModule);
            $this->installData['id'] = '\'seed-' . $currentModule . substr(md5($this->installData['id']), 0, -($moduleLength + 1)).  "'";
        }
    }

    function clean(){
        $this->installData = array();
        $this->count = 0;
    }


    /**
     * Dispatch to the handleType function based on what values are present in the
     * global $dataTool array.  This array is populated by the .php files in the
     * TidBit/Data directory.
     *
     * Priority: FieldName > FieldDBType > FieldSugarType
     *
     * @param $fieldName - name of the field for which data is being generated
     * @param $fieldDBType - The DB type of the field, if it differs from the Sugar type
     * @param $sugarType - Always the Sugar type of the field
     * @param $seed - Seed from generateSeed(), used to generate a random reasonable value
     *
     * @return string
     */
    function getData($fieldName, $fieldDBType, $sugarType, $seed){
        //echo "GD: $fieldName, $fieldType, $sugarType, $seed\n";
        // Check if the fieldName is defined
        if(!empty($GLOBALS['dataTool'][$this->module][$fieldName])){
            return $this->handleType($GLOBALS['dataTool'][$this->module][$fieldName], $fieldDBType, $fieldName, $seed);
        }

        // Check if fieldType is defined
        if(!empty($GLOBALS['dataTool'][$this->module][$fieldDBType])){
            return $this->handleType($GLOBALS['dataTool'][$this->module][$fieldDBType], $fieldDBType, $fieldName, $seed);
        }

        // Check if the Sugar type is defined
        if(!empty($GLOBALS['dataTool'][$this->module][$sugarType])){
            return $this->handleType($GLOBALS['dataTool'][$this->module][$sugarType], $fieldDBType, $fieldName, $seed);
        }

        // If the fieldName is undefined for this module, see if a default value is defined
        if(!empty($GLOBALS['dataTool']['default'][$fieldName])){
            return $this->handleType($GLOBALS['dataTool']['default'][$fieldName], $fieldDBType, $fieldName, $seed);
        }

        // If the fieldType is undefined for this module, see if a default value is defined
        if(!empty($GLOBALS['dataTool']['default'][$fieldDBType])){
            return $this->handleType($GLOBALS['dataTool']['default'][$fieldDBType], $fieldDBType, $fieldName, $seed);
        }

        // If the sugarType is undefined for this module, see if a default value is defined
        if(!empty($GLOBALS['dataTool']['default'][$sugarType])){
            return $this->handleType($GLOBALS['dataTool']['default'][$sugarType], $fieldDBType, $fieldName, $seed);
        }

        return '';
    }

	/**
	 * Returns a randomly generated piece of data for the current module and field.  
	 * @param $typeData - An array from a .php file in the Tidbit/Data directory
	 * @param $type - The type of the current field
	 * @param $field - The name of the current field
	 * @param $seed - Number to be used as the seed for mt_srand()
	 */
    function handleType($typeData, $type, $field, $seed){
        /* We want all data to be predictable.  $seed should be charactaristic of 
         * this entity or the remote entity we want to simulate
         */
        mt_srand($seed);
        
//        echo "HT: $typeData, $type, $field, $seed\n";
        if(!empty($typeData['skip']))return '';

        // Set TeamSet with all existings teams
        if (!empty($typeData['teamset_max'])) {
            return $this->installData['team_set_id'] = self::$max_team_set_id;
        }

        if(!empty($typeData['teamset'])) {
        	$index = rand(0, count(self::$team_sets_array)-1);
        	$keys = array_keys(self::$team_sets_array);
            return $this->installData['team_set_id'] = $keys[$index];
        }        
        
        if(!empty($typeData['value']) || (isset($typeData['value']) && $typeData['value']=="0")){
            return $typeData['value'];
        }
        if(!empty($typeData['increment'])){
            static $inc = -1;
            $inc ++;
            if($typeData['increment']['max']){
            	return $typeData['increment']['min'] + ($inc % ($typeData['increment']['max']-$typeData['increment']['min']));
            }else{
                return $typeData['increment']['min'] + $inc;
            }
        }
        /* This gets used for usernames, which need to be
         * user1, user2 etc.
         */
        if(!empty($typeData['incname'])){
            static $ninc = 0;
            $ninc ++;
            return "'".@trim($typeData['incname'].$ninc)."'";
        }

        if(!empty($typeData['autoincrement'])){
            if($GLOBALS['sugar_config']['dbconfig']['db_type'] == 'oci8'
               || $GLOBALS['sugar_config']['dbconfig']['db_type'] == 'ibm_db2'){
                return strtoupper($this->table_name. '_' . $field . '_seq') . '.nextval';
            }else{
                return '';
            }
        }
        if(!empty($typeData['autoincrement'])){
            if($GLOBALS['sugar_config']['dbconfig']['db_type'] != 'oci8'){
                return '';
            }else{
            	return strtoupper($this->table_name. '_' . $field . '_seq') . '.nextval';
            }
        }
        /* This type alternates between two specified options */
        if(!empty($typeData['binary_enum'])){
            static $inc = -1;
            $inc ++;
            return $typeData['binary_enum'][$inc % 2];
        }
        if(!empty($typeData['sum'])){
            $sum = 0;
            foreach($typeData['sum'] as $piece){
                /* If it is a string, access the
                 * value of that field.  Otherwise
                 * just treat it as a number.
                 */
                if(is_string($piece)){
                    $value = $this->accessLocalField($piece);
                    if(is_numeric($value)){
                    	$sum += $value;
                    }
                }else{
                	$sum += $piece;
                }
            }
            return $sum;
        }
        if(!empty($typeData['sum_ref'])){
            $sum = 0;
            foreach($typeData['sum_ref'] as $piece){
                $sum += $this->accessRemoteField($piece['module'], $piece['field']);
            }
            return $sum;
        }
        if(!empty($typeData['same'])){
            if(is_string($typeData['same']) && !empty($this->fields[$typeData['same']])){
                //return $this->accessLocalField($typeData['same']);
                $rtn = $this->accessLocalField($typeData['same']);
            }else{
                //return $typeData['same'];
                $rtn = $typeData['same'];
            }
            if (!empty($typeData['toUpper'])) {
            	$rtn = strtoupper($rtn);
            }

            if (!empty($typeData['toLower'])) {
                $rtn = strtolower($rtn);
            }

            return @trim($rtn);
        }
        if(!empty($typeData['same_ref'])){
            /* We aren't going to consider literal values,
             * because you can just use 'same' for that.
             */ 
            //echo "SR: ";
            return @trim($this->accessRemoteField($typeData['same_ref']['module'], $typeData['same_ref']['field']));
        }
        if(!empty($typeData['same_hash'])){
            if(is_string($typeData['same_hash']) && !empty($this->fields[$typeData['same_hash']])){
                $value = $this->accessLocalField($typeData['same_hash']);
                if(is_string($value)){
                	$value = substr($value, 1, strlen($value)-2);
                }
                return "'".md5($value)."'";
            }else{
                return "'".md5($typeData['same_hash'])."'";
            }
        }
        if(!empty($typeData['related'])){
            if(!empty($typeData['related']['ratio'])){
            	$thisToRelatedRatio = $typeData['related']['ratio'];
            }else{
            	$thisToRelatedRatio = 0;
            }

            $relModule = $this->getAlias($typeData['related']['module']);
            $relUpID = $this->getRelatedUpId($typeData['related']['module'], $thisToRelatedRatio);
            $relatedId = $this->assembleId($relModule, $relUpID);

            return $relatedId;
        }

        if (!empty($typeData['gibberish'])) {

            $baseValue = @trim($this->generateGibberish($typeData['gibberish']));

            // Check field length and truncate data depends on vardefs length
            if (!empty($GLOBALS['fieldData']['len']) && $GLOBALS['fieldData']['len'] < strlen($baseValue)){
                $baseValue = $this->truncateDataByLength($baseValue, (string) $GLOBALS['fieldData']['len']);
            }

            return "'" . $baseValue . "'";
        }
        
        if(!empty($typeData['meeting_probability'])){
            /* If this is for meetings, and it's in the past,
             * we need to adjust the probability.
             * Note that this will break if date_start comes after
             * status in the vardefs for Meetings :-/.
             */
            if(!empty($GLOBALS['fieldData']['options']) && !empty($GLOBALS['app_list_strings'][$GLOBALS['fieldData']['options']])){
                $options = $GLOBALS['app_list_strings'][$GLOBALS['fieldData']['options']];
                $keys = array_keys($options);
                /* accessLocalField loads the value of that field or
                 * computes it if it has not been computed.
                 */
                $startDate = $this->accessLocalField('date_start');
                
                $stamp = strtotime(substr($startDate, 1, strlen($startDate)-2));
                if($stamp >= mktime()){
                    $rn = mt_rand(0, 9);
                    /* 10% chance of being NOT HELD - aka CLOSED */
                    if($rn > 8){
                        $selected = 2;
                    }
                    else{
                        $selected = 0;
                    }
                }
                else{
                    $rn = mt_rand(0, 49);
                    /* 2% chance of being HELD - aka OPEN */
                    if($rn > 48){
                        $selected = 0;
                    }
                    else{
                        $selected = 2;
                    }
                }
            }
            return "'" . @trim($keys[$selected]) . "'";
        }
        
        $isQuote = true;
        //we have a range then it should either be a number or a date
        $baseValue = '';
        if(!empty($typeData['range'])){
            
            $baseValue = mt_rand($typeData['range']['min'], $typeData['range']['max']);
            
            if(!empty($typeData['multiply'])){
                $baseValue *= $typeData['multiply'];
            }
            //everything but numbers must have a type so we are just a range
            if(!empty($typeData['type'])){
                $isQuote = true;
                $basetime = (!empty($typeData['basetime']))?$typeData['basetime']: time();
                if(!empty($baseValue)){
                    $basetime += $baseValue * 3600 * 24;
                }
                switch($typeData['type']){
                    case 'date': $baseValue = date('Y-m-d', $basetime); break;
                    case 'datetime': $baseValue = date('Y-m-d H:i:s', $basetime); break;
                    case 'time': $baseValue = date('H:i:s', $basetime); break;
                }
                
            }else{
                $isQuote = false;
            }
        }else if(!empty($typeData['list']) && !empty($GLOBALS[$typeData['list']])){
            $selected = mt_rand(0, count($GLOBALS[$typeData['list']]) - 1);
            $baseValue = $GLOBALS[$typeData['list']][$selected];
        }

        // Handle date_start/date_end logic there
        if (!empty($typeData['same_datetime']) && !empty($this->fields[$typeData['same_datetime']])) {
            $baseValue = $this->accessLocalField($typeData['same_datetime']);
            $baseValue = str_replace('\'', '', $baseValue);

            // Apply datetime modifications
            // Calculate modifications (e.g hours and minutes) and shift current base value
            if (!empty($typeData['modify']) && is_array($typeData['modify'])) {

                $shift = 0;

                foreach ($typeData['modify'] as $type => $value) {
                    // If value is depending on another field - let's get field value, otherwise - use value
                    if (is_array($value) && !empty($value['field']) && !empty($this->fields[$value['field']])) {
                        $timeUnit = $this->accessLocalField($value['field']);
                    } else {
                        $timeUnit = $value;
                    }

                    $shift += $this->applyDatetimeModifications($type, $timeUnit);
                }

                $baseValue = date('Y-m-d H:i:s', strtotime($baseValue) + $shift);
            }
        }

        if(!empty($typeData['suffixlist'])){
            foreach($typeData['suffixlist'] as $suffixlist){
               
                if(!empty($GLOBALS[$suffixlist])){
                 $selected = mt_rand(0, count($GLOBALS[$suffixlist]) - 1);
                 $baseValue .= ' ' .$GLOBALS[$suffixlist][$selected];
                }
            }
        }else if($type == 'enum'){
            
            if(!empty($GLOBALS['fieldData']['options']) && !empty($GLOBALS['app_list_strings'][$GLOBALS['fieldData']['options']])){
                $options = $GLOBALS['app_list_strings'][$GLOBALS['fieldData']['options']];
                $keys = array_keys($options);
                
                $selected = mt_rand(0, count($keys) - 1);
                return "'" . @trim($keys[$selected]). "'";
                
            }
        }
     	// This is used to associate email addresses with rows in
        // Contacts or Leads.  See Relationships/email_addr_bean_rel.php
        if (!empty($typeData['getmodule'])) {
        	$rtn = "'" . $this->module . "'";
        	return $rtn;
        }
         if(!empty($typeData['prefixlist'])){
            foreach($typeData['prefixlist'] as $prefixlist){
                if(!empty($GLOBALS[$prefixlist])){
                 $selected = mt_rand(0, count($GLOBALS[$prefixlist]) - 1);
                 $baseValue = $GLOBALS[$prefixlist][$selected] . ' ' . $baseValue;
                }
            }
        }
       
        if(!empty($typeData['suffix'])){
            $baseValue .= $typeData['suffix'];
        }
        if(!empty($typeData['prefix'])){
            $baseValue = $typeData['prefix'] . $baseValue;
            
        }

        if (!empty($GLOBALS['fieldData']['len']) && $GLOBALS['fieldData']['len'] < strlen($baseValue)) {
           $baseValue = $this->truncateDataByLength($baseValue, (string) $GLOBALS['fieldData']['len']);
        }

        if($isQuote || !empty($typeData['isQuoted']) ){
            $baseValue = "'".@trim($baseValue) . "'";
        }

        // Run db convert only for specific types. see DBManager::convert()
        if (!in_array($type, self::$notConvertedTypes)) {
            $baseValue = $GLOBALS['db']->convert($baseValue, $type);
        }

        return $baseValue;
    }

    /**
     * Truncate data value by VarDefs length
     *
     * @param $value - data base value
     * @param $length - could be "integer" or float length value, f.e. "5,2"
     * @return string
     */
    protected function truncateDataByLength($value, $length)
    {
        $arr = explode(",", $length, 2);
        $baseLength = $arr[0];
        return substr($value, 0, $baseLength);
    }

    /**
     * Returns the value of this module's field called $fieldname.
     * If a value has already been generated, it uses that one, otherwise
     * it calls getData() to generate the value.
     * @param $fieldName - Name of the local field you want to retrieve
     */
    function accessLocalField($fieldName){
        /* TODO - OPTIMIZATION - if we have to render the data,
         * then save it to installData so we only do it once.
         * Make sure that generateData checks for it.
         * Note that this is safe even when used as a foreign
         * object, becuase accessRemoteField calls clean each time.
         */
        /* We will only deal with fields defined in the
         * vardefs.
         */
        if(!empty($this->fields[$fieldName])){
            /* If this data has already been generated,
             * then just use it.
             */
            if(!empty($this->installData[$fieldName])){
                //echo "AAA: {$this->installData[$fieldName]}\n";
                return $this->installData[$fieldName];
            /* Otherwise, we have to pre-render it. */
            }else{
                $recSeed = $this->generateSeed($this->module, $fieldName, $this->count);
                $recData = $this->fields[$fieldName];
                $recType = (!empty($recData['dbType']))?$recData['dbType']:$recData['type'];
                //echo "BBB: $fieldName, $recType, {$recData['type']}, $recSeed\n";
                return $this->getData($fieldName, $recType, $recData['type'], $recSeed);
            }
        }else{
            return $fieldName;
        }
    }
    
    
    /**
     * Returns the value of $module's field called $fieldName. 
     * Calls accessLocalField($fieldName) on a separate DataTool object
     * for the remote module.
     * @param $module - Name of remote module to access.
     * @param $fieldName - Name of field in remote module to retrieve.
     */
    function accessRemoteField($module, $fieldName){
        /* Form is 'Module' => field */
        /* I need to call $this->getData. */
        /* I need to load the Data/Module.php file,
         * to make a proper call to getData. */
        /* I need the var_defs for the Module, so
         * I can access its type or dbType. (only if it's an enum...)*/
        /* I also need the remote count of the 'parent' or 'related'
         * module.  This count would be the one that I get when
         * I generate relationships or fill 'related' fields.
         */
        /* But getData looks at $this->module... ick */
        /* Well, I'm loading the vardefs for this class, so I might
         * as well load a new dataTool for it.
         */
        /* 1. Load Data/Module definitions
         * 2. Load class[Module]
         * 3. Identify related count - ?
         * 4. call getData on the one field we want.
         */
        
        /* Should one accidentally refer to itsself, just call local */
        if($module == $this->module) return $this->accessLocalField($fieldName);
        
        /* Check if a cached dataTool object exists. */
        if(!empty($GLOBALS['foreignDataTools']) && !empty($GLOBALS['foreignDataTools'][$module])){
        	$rbfd = $GLOBALS['foreignDataTools'][$module];
        }else{
            include('include/modules.php');
            $class = $beanList[$module];
            require_once($beanFiles[$class]);
            $bean = new $class();
            if(file_exists('Tidbit/Data/' . $bean->module_dir . '.php')){
                require_once('Tidbit/Data/' . $bean->module_dir . '.php');
            }
            $rbfd = new DataTool();
            $rbfd->fields = $bean->field_defs;
            $rbfd->table_name = $bean->table_name;
            $rbfd->module = $module;
            /* Cache the dataTool object. */
            $GLOBALS['foreignDataTools'][$module] = $rbfd;
        }
        $rbfd->clean();
        $rbfd->count = $this->getRelatedUpId($module);
        return $rbfd->accessLocalField($fieldName);
    }
    
    
    
    /**
     * Generate a 'related' id for use
     * by handleType:'related' and 'generateRelationships'
     */
    function getRelatedId($relModule, $baseModule, $thisToRelatedRatio = 0){
        if(empty($GLOBALS['counters'][$this->module.$relModule])){
        	$GLOBALS['counters'][$this->module.$relModule] = 0;
        }
        
        $c = $GLOBALS['counters'][$this->module.$relModule];
        
        /* All ratios can be determined simply by looking
         * at the relative counts in $GLOBALS['modules']
         */
        /* Such a module must exist. */
        if(!empty($GLOBALS['modules'][$relModule])){
            $baseToRelatedRatio = $GLOBALS['modules'][$relModule] / $GLOBALS['modules'][$baseModule];
            $baseToThisRatio = $GLOBALS['modules'][$this->module] / $GLOBALS['modules'][$baseModule];
            
            /* floor($this->count/$acctToThisRatio) determines what 'group'
             * this record is a part of.  Multiplying by $acctToRelatedRatio
             * gives us the starting record for the group of the related type.
             */
            $n = floor(floor($this->count/$baseToThisRatio)*$baseToRelatedRatio);
            
            $GLOBALS['counters'][$this->module.$relModule]++;
            
            /* There are $acctToRelatedRatio of the related types
             * in the group, so we can just pick one of them.
             */            
            return $n + ($c%ceil($baseToRelatedRatio));
        }
    }
    
    
    
    /**
     * Generate a 'parent' id for use
     * by handleType:'parent'
     */
    function getRelatedUpId($relModule, $thisToRelatedRatio = 0){
        /* The relModule that we point up to should be the base */
        return $this->getRelatedId($relModule,$relModule,$thisToRelatedRatio);
    }
    
    
    /**
     * Generate a 'parent' id for use
     * by handleType:'parent'
     * ToDo: add mapping for $baseModule.
     */
    function getRelatedLinkId($relModule, $thisToRelatedRatio = 0){
        /* The baseModule needs to be Accounts normally
         * but we need to keep Quotes inclusive
         * and Teams and Users, which are above Accounts,
         * need to have themselves as the base.
         */
        if($relModule == 'Teams'){
            $baseModule = 'Teams';
        } elseif ($this->module == 'ACLRoles') {
            $baseModule = 'ACLRoles';
        }elseif($this->module == 'Users'){
            $baseModule = 'Users';
        }elseif($this->module == 'ProductBundles'){
            $baseModule = 'Quotes';
        }else{
            $baseModule = 'Accounts'; 
        }
        
        return $this->getRelatedId($relModule,$baseModule,$thisToRelatedRatio);
    }
    
    
    /**
     * Creates the query head and query bodies, and saves them in global arrays
     * $queryHead and $queries, respectively.
     */
    function createInserts(){
        if(empty($GLOBALS['queryHead'])){
            $GLOBALS['queryHead'] = $this->createInsertHead($this->table_name);
        }
        
        $GLOBALS['queries'][] = $this->createInsertBody();
        
        $_SESSION['allProcessedRecords']++;
    }
    
    function createInsertHead($table){
        return 'INSERT INTO ' . $table . ' ( ' .implode(', ',array_keys($this->installData)) . ') VALUES ';
    }
    
    function createInsertBody(){
        return '  (  ' .implode(', ',array_values($this->installData)). ' )';
    }
    
    
    /**
     * Generates and saves queries to create relationships in the Sugar app, based
     * on the contents of the global array $tidbit_relationships.
     */
    function generateRelationships(){
        global $relQueryCount;
        
        $baseId = $this->installData['id'];
    	
        if(empty($GLOBALS['tidbit_relationships'][$this->module]))return;
        
        foreach($GLOBALS['tidbit_relationships'][$this->module] as $relModule=>$relationship){

            // TODO: remove this check or replace with something else
            if (!is_dir('modules/' . $relModule)) {
                continue;
            }

            if(!empty($GLOBALS['modules'][$relModule])){
                
                
                if(!empty($relationship['ratio'])){
                    $thisToRelatedRatio = $relationship['ratio'];
                }else{
                	$thisToRelatedRatio = $GLOBALS['modules'][$relModule] / $GLOBALS['modules'][$this->module];
                }
                
                /* Load any custom feilds for this relationship */
                if(file_exists('Tidbit/Relationships/' . $relationship['table'] . '.php')){
                	//echo "\n". 'loading custom fields from ' . 'Tidbit/Relationships/' . $relationship['table'] . '.php' . "\n";
                    require_once('Tidbit/Relationships/' . $relationship['table'] . '.php');
                }
                
                /* According to $relationship['ratio'],
                 * we attach that many of the related object to the current object
                 * through $relationship['table']
                 */
                for($j = 0; $j < $thisToRelatedRatio; $j++){

                    $currentRelModule = $this->getAlias($relModule);
                    $relId = $this->assembleId($currentRelModule, $this->getRelatedLinkId($relModule), false);

                    $relOverridesStore = array();
                    /* If a repeat factor is specified, then we will process the body multiple times. */
                    if(!empty($GLOBALS['dataTool'][$relationship['table']]) && !empty($GLOBALS['dataTool'][$relationship['table']]['repeat'])){
                    	$multiply = $GLOBALS['dataTool'][$relationship['table']]['repeat']['factor'];
                        /* We don't want 'repeat' to get into the DB, but we'll put it back into
                         * the globals later.
                         */
                        $relOverridesStore = $GLOBALS['dataTool'][$relationship['table']];
                        unset($GLOBALS['dataTool'][$relationship['table']]['repeat']);
                    }else{
                    	$multiply = 1;
                    }
                    
                    /* Normally $multiply == 1 */
                    while($multiply--){
                        $GLOBALS['relatedQueries'][$relationship['table']][] =  $this->generateRelationshipBody($relationship, $baseId, $relId);
                    }
                    
                    $_SESSION['allProcessedRecords']++;
                    
                    if(empty($GLOBALS['relatedQueries'][$relationship['table']]['head'])){
                        $GLOBALS['relatedQueries'][$relationship['table']]['head'] = $this->generateRelationshipHead($relationship);
                    }
                    
                    /* Restore the relationship settings */
                    if($relOverridesStore){
                    	$GLOBALS['dataTool'][$relationship['table']] = $relOverridesStore;
                    }
                    
                    $relQueryCount++;
                }
            }
        }
    }
    
    /**
     * Returns the common head shared by all the current relationship queries.
     * @param $relationship - Array defining the relationship, from global $tidbit_relationships[$module]
     */
    function generateRelationshipHead($relationship){
        /* Include custom fields in our header */
        $customFields = '';
        if(!empty($GLOBALS['dataTool'][$relationship['table']])){
            foreach($GLOBALS['dataTool'][$relationship['table']] as $field => $typeData){
                $customFields .= ', ' . $field;
            }
        }
        return  'INSERT INTO ' . $relationship['table'] . '(id, '
                . $relationship['self'] . ', ' . $relationship['you']
                . ',' . 'deleted, date_modified' . $customFields
                .') VALUES ';
    
    }
    
    /**
     * Returns the body for the current relationship query.
     *
     * @param $relationship - Array defining the relationship, from global $tidbit_relationships[$module]
     * @param $baseId - Current module id
     * @param $relId - Id for the related module
     * @return string
     */
    function generateRelationshipBody($relationship, $baseId, $relId)
    {
        static $relCounter = 0;

        if ($relCounter == 0) {
            $relCounter = !empty($_GET['offset']) ? $_GET['offset'] : 0;
        }

        $relCounter++;
        $date = $this->getConvertDatetime();
        $customData = '';
        
        if (!empty($GLOBALS['dataTool'][$relationship['table']])) {
            foreach($GLOBALS['dataTool'][$relationship['table']] as $field => $typeData){
                $seed = $this->generateSeed($this->module, $field, $this->count);
                $customData .= ', ' . $this->handleType($typeData, '', $field, $seed);
            }
        }

        return  ' (' ."'seed-rel" . time() . $relCounter . "',$baseId , '$relId' , 0 , $date".$customData." )";
    }


    /**
     * Cache datetime generation and convert to db format
     * Based on xhprof data, this operation in time consuming, so we need to cache that
     *
     * @return mixed
     */
    function getConvertDatetime()
    {
        static $datetime = '';

        self::$datetimeCacheIndex++;

        if ((self::$datetimeCacheIndex > self::$datetimeIndexMax) || empty($datetime)) {
            $datetime = $GLOBALS['db']->convert("'".date('Y-m-d H:i:s') ."'", 'datetime') ;
            self::$datetimeCacheIndex = 0;
        }

        return $datetime;
    }

    /**
     * Returns a gibberish string based on a reordering of the base text
     * (Lorem ipsum dolor sit amet, ....)
     *
     * @param $wordCount
     * @return string
     */
    function generateGibberish($wordCount = 1)
    {
        static $words = array();

        if (empty($words)) {
            $baseText = "Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nunc pulvinar tellus et arcu. Integer venenatis nonummy risus. Sed turpis lorem, cursus sit amet, eleifend at, dapibus vitae, lacus. Nunc in leo ac justo volutpat accumsan. In venenatis consectetuer ante. Proin tempus sapien et sapien. Nunc ante turpis, bibendum sed, pharetra a, eleifend porta, augue. Curabitur et nulla. Proin tristique erat. In non turpis. In lorem mauris, iaculis ac, feugiat sed, bibendum eu, enim. Donec pede. Phasellus sem risus, fermentum in, imperdiet vel, mattis nec, justo. Nullam vitae risus. Fusce neque. Mauris malesuada euismod magna. Sed nibh pede, consectetuer quis, condimentum sit amet, pretium ut, eros. Quisque nec arcu. Sed ac neque. Maecenas volutpat erat ac est. Nam mauris. Sed condimentum cursus purus. Integer facilisis. Duis libero ante, cursus nec, congue nec, imperdiet et, ligula. Pellentesque porttitor suscipit nulla. Integer diam magna, luctus rutrum, luctus sit amet, euismod a, diam. Nunc vel eros faucibus velit lobortis faucibus. Phasellus ultrices, nisl id pulvinar fringilla, justo augue elementum enim, eget tincidunt dolor pede et tortor. Vestibulum at justo vitae sem auctor tincidunt. Maecenas facilisis volutpat dui. Pellentesque non justo. Quisque eleifend, tellus quis venenatis volutpat, ipsum purus cursus dolor, in aliquam magna sem.";
            $words = explode(' ', $baseText);
        }

        shuffle($words);

        if ($wordCount > 0) {
            $words = array_slice($words, 0, $wordCount);
        }

        return implode(' ', $words);
    }

    /**
     * Assemble Bean id string by module and related/count IDs
     *
     * @param string $module
     * @param int $id
     * @param bool $quotes
     * @return string
     */
    function assembleId($module, $id, $quotes = true)
    {
        static $assembleIdCache = array();

        if (empty($assembleIdCache[$module])) {
            $assembleIdCache[$module] = (($module == 'Users') || ($module == 'Teams'))
                ? 'seed-' . $module
                : 'seed-' . $module . $_SESSION['baseTime'];
        }

        $seedId = $assembleIdCache[$module] . $id;

        // should return id be quoted or not
        if ($quotes) {
            $seedId = "'" . $seedId . "'";
        }

        return $seedId;
    }

    /*
     * TODO - OPTIMIZATION - cache sums of $fieldName and $this->module
     * somewhere, sessions maybe.
     * DONE: cache in static properties
     */
    /**
     * Returns a seed to be used with the RNG.
     *
     * @param string $module - The current module
     * @param string $field - The current field
     * @param int $count - The current record number
     * @return int
     */
    function generateSeed($module, $field, $count)
    {
        // Cache module
        if (!isset(self::$seedModules[$module])) {
            self::$seedModules[$module] = $this->str_sum($this->module);
        }

        // Cache fields
        if (!isset(self::$seedFields[$field])) {
            self::$seedFields[$field] = $this->str_sum($field);
        }

        /*
         * We multiply by two because mt_srand
         * doesn't work well when you give it
         * consecutive integers.
         */
        return  2 * (self::$seedModules[$module] + self::$seedFields[$field] + $count + $_SESSION['baseTime']);
    }

    /**
     * Returns an alias to be used for id generation.
     * @param $name - The current module
     * @return string
     */
    function getAlias($name) {
        global $aliases;
        return (isset ($aliases[$name]))
            ? $aliases[$name]
            : $name;
    }

    /**
     * Calculate string check sum
     *
     * @param $str
     * @return int
     */
    function str_sum($str)
    {
        $sum = 0;

        for ($i = strlen($str); $i--;) {
            $sum += ord($str[$i]);
        }

        return $sum;
    }

    /**
     * Calculate datetime shift depending on type
     *
     * @param $type
     * @param $value
     * @return int
     */
    public function applyDatetimeModifications($type, $value)
    {
        // default shift is one minute
        $shift = 60;

        switch ($type) {
            case 'hours' :
                $shift = 3600;
                break;
            case 'minutes' :
                $shift = 60;
                break;
            case 'days' :
                $shift = 24 * 3600;
        }

        // Return N(days/hours/minutes)*$shift = number of seconds to shift
        return $value * $shift;
    }
}
