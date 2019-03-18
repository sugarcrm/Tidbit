<?php

/*********************************************************************************
 * Tidbit is a data generation tool for the SugarCRM application developed by
 * SugarCRM, Inc. Copyright (C) 2004-2016 SugarCRM Inc.
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

/**
 * DataTool randomly generates data to be inserted into the Sugar application
 * A DataTool object corresponds to a Sugar module.
 * install_cli.php creates a DataTool object for each Sugar module and
 * initializes its fields based on values from that Sugar module.
 *
 */

namespace Sugarcrm\Tidbit;

use Sugarcrm\Tidbit\Core\Intervals;
use Sugarcrm\Tidbit\FieldData\Phone;
use Sugarcrm\Tidbit\StorageAdapter\Factory;
use Sugarcrm\Tidbit\Core\Factory as CoreFactory;

class DataTool
{
    /** @var array stores data to insert into %module% table */
    public $installData = array();

    /** @var array stores data to insert into %module_cstm% table */
    public $installDataCstm = array();

    /** @var array stores fields and its values from vardefs.php for every module */
    protected $fields = array();

    /** @var array stores field rules */
    protected $fieldRules = [];

    public $table_name = '';
    public $module = '';
    public $count = 0;

    // based on xhprof, db_convert for datetime and time generation, consume a lot of time.
    // getConvertDatetime() function re-generate time only when this index reach max number
    // so we will re-generate time only for self::$datetimeIndexMax record
    protected static $datetimeCacheIndex = 0;
    protected static $datetimeIndexMax = 1000;

    /**
     * Type of output storage
     *
     * @var string
     */
    protected $storageType;

    /** @var Intervals  */
    protected $coreIntervals;

    // Skip db_convert for those types for optimization
    protected static $notConvertedTypes = array(
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
     * DataTool constructor.
     * @param string $storageType
     */
    public function __construct($storageType)
    {
        $this->storageType = $storageType;
        $this->coreIntervals = CoreFactory::getComponent('Intervals');
    }

    /**
     * Getter for filtered fields data from vardef.php
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * It takes fields from vardef.php for a module, filters it and sets filtered array to $fields variable.
     * It removes fields for which don't need to generate data.
     * Filtering reduce time for iterating fields.
     *
     * $isNonDB - Don't iterate 'non-db' (and similar to it) fields
     * $isSkipped - Skip fields which are marked for skipping in tidbit's config for module
     * $isMysqlAutoincrement - There is no need to generate field data for autoincrement fields in MySQL
     *
     * @param $fieldDefs array - Value of 'fields' key from vardef.php for a module.
     */
    public function setFields($fieldDefs)
    {
        $this->fieldRules = [];
        $this->fields = [];

        foreach ($fieldDefs as $fieldName => $fieldDef) {
            // skip non-db fields
            if (isset($fieldDef['source']) && $fieldDef['source'] != 'custom_fields') {
                continue;
            }

            $type = !empty($fieldDef['dbType']) ? $fieldDef['dbType'] : $fieldDef['type'];
            $fieldRules = false;
            foreach ([$this->module, 'default'] as $module) {
                foreach ([$fieldName, $type, $fieldDef['type']] as $fieldKind) {
                    if (isset($GLOBALS['dataTool'][$module][$fieldKind])) {
                        $fieldRules = $GLOBALS['dataTool'][$module][$fieldKind];
                        break 2;
                    }
                }
            }
            if ($fieldRules === false) {
                trigger_error("No generation rules were found for $fieldName field of $this->module module");
                continue;
            }

            // skip skipped fields
            if (!empty($fieldRules['skip'])) {
                continue;
            }

            // skip autoincrement fields when storage is mysql
            // because mysql generates values automatically in this case
            if ($this->storageType == 'mysql'
                && (!empty($fieldDef['auto_increment']) || !empty($fieldRules['autoincrement']))
            ) {
                continue;
            }

            // enum probability option
            if ($type === 'enum' && !empty($fieldDef['options'])) {
                $fieldRules['enum_key_probabilities'] = $this->calcEnumProbabilities(
                    $fieldDef,
                    $fieldRules['enum_key_probabilities'] ?? []
                );
            }

            $this->fieldRules[$fieldName] = $fieldRules;
            $this->fields[$fieldName] = $fieldDef;
        }
    }

    /**
     * Generate data and store it in the installData array.
     * This is done for each field.
     */
    public function generateData()
    {
        foreach ($this->fields as $field => $data) {
            if (isset($this->installData[$field])) {
                continue;
            }
            $this->generateFieldData($field);
        }
    }

    /**
     * Generates data for provided field name based on field definition in vardef.php for proper module
     *
     * @param $field string - field name
     */
    protected function generateFieldData($field)
    {
        $type = (!empty($this->fields[$field]['dbType']))
            ? $this->fields[$field]['dbType']
            : $this->fields[$field]['type'];

        $GLOBALS['fieldData'] = $this->fields[$field];

        $value = $this->handleType($this->fieldRules[$field], $type, $field);

        if (empty($this->fields[$field]['source'])) {
            $this->installData[$field] = $value;
        } else {
            $this->installDataCstm[$field] = $value;
        }
    }

    /**
     * Generate a unique ID based on the module name, system time, and count (defined
     * in configs for each module), and save the ID in the installData array.
     *
     * @param bool $includeCustomId
     * @return string
     */
    public function generateId($includeCustomId = false)
    {
        if (!isset($this->fields['id'])) {
            return '';
        }

        $this->installData['id'] = $this->coreIntervals->generateTidbitID($this->count, $this->module);

        if ($includeCustomId) {
            $this->installDataCstm['id_c'] = $this->installData['id'];
        }

        return substr($this->installData['id'], 1, -1);
    }

    public function clean()
    {
        $this->installData = array();
        $this->installDataCstm = array();
        $this->count = 0;
    }


    /**
     * Returns a randomly generated piece of data for the current module and field.
     *
     * @param $typeData - An array from a .php file in the Tidbit/Data directory
     * @param $type - The type of the current field
     * @param $field - The name of the current field
     *
     * // DEV TESTING ONLY
     * @param bool $resetStatic
     *
     * @return string
     * @throws \Exception
     */
    public function handleType($typeData, $type, $field, $resetStatic = false)
    {
        if (!empty($typeData['skip'])) {
            return '';
        }

        if (!empty($typeData['value']) || (isset($typeData['value']) && $typeData['value'] == "0")) {
            return $typeData['value'];
        }
        if (!empty($typeData['increment'])) {
            static $inc = -1;

            if ($resetStatic) {
                $inc = -1;
            }

            $inc++;
            if ($typeData['increment']['max']) {
                return $typeData['increment']['min'] +
                ($inc % ($typeData['increment']['max'] - $typeData['increment']['min']));
            } else {
                return $typeData['increment']['min'] + $inc;
            }
        }
        /* This gets used for usernames, which need to be
         * user1, user2 etc.
         */
        if (!empty($typeData['incname'])) {
            return "'" . @trim($typeData['incname'] . $this->count) . "'";
        }

        if (!empty($typeData['autoincrement'])) {
            if ($this->storageType == Factory::OUTPUT_TYPE_ORACLE
                || $this->storageType == Factory::OUTPUT_TYPE_DB2
            ) {
                return strtoupper($this->table_name . '_' . $field . '_seq.nextval');
            } elseif ($this->storageType == Factory::OUTPUT_TYPE_CSV) {
                return $this->count + 1;
            } else {
                return '';
            }
        }

        /* This type alternates between two specified options */
        if (!empty($typeData['binary_enum'])) {
            static $inc = -1;

            if ($resetStatic) {
                $inc = -1;
            }

            $inc++;
            return $typeData['binary_enum'][$inc % 2];
        }
        if (!empty($typeData['sum'])) {
            $sum = 0;
            foreach ($typeData['sum'] as $piece) {
                /* If it is a string, access the
                 * value of that field.  Otherwise
                 * just treat it as a number.
                 */
                if (is_string($piece)) {
                    $value = $this->accessLocalField($piece);
                    if (is_numeric($value)) {
                        $sum += $value;
                    }
                } else {
                    $sum += $piece;
                }
            }
            return $sum;
        }
        if (!empty($typeData['same'])) {
            if (is_string($typeData['same']) && !empty($this->fields[$typeData['same']])) {
                $rtn = $this->accessLocalField($typeData['same']);
            } else {
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
        if (!empty($typeData['same_sugar_hash'])) {
            return $this->getSameSugarHash($typeData['same_sugar_hash']);
        }
        if (!empty($typeData['same_hash'])) {
            if (is_string($typeData['same_hash']) && !empty($this->fields[$typeData['same_hash']])) {
                $value = $this->accessLocalField($typeData['same_hash']);
                if (is_string($value)) {
                    $value = substr($value, 1, strlen($value) - 2);
                }
                return "'" . md5($value) . "'";
            } else {
                return "'" . md5($typeData['same_hash']) . "'";
            }
        }
        if (!empty($typeData['related'])) {
            $modules = is_array($typeData['related']['module'])
                ? $typeData['related']['module']
                : [$typeData['related']['module']];

            $relN = $this->count;
            $baseModule = $this->module;
            foreach ($modules as $module) {
                $relN = $this->coreIntervals->getRelatedId($relN, $baseModule, $module);
                $baseModule = $module;
            }

            $relModule = $this->coreIntervals->getAlias($baseModule);
            $relatedId = $this->coreIntervals->assembleId($relModule, $relN);
            return $relatedId;
        }

        if (!empty($typeData['gibberish'])) {
            $baseValue = @trim($this->generateGibberish($typeData['gibberish']));

            // Check field length and truncate data depends on vardefs length
            if (!empty($GLOBALS['fieldData']['len']) && $GLOBALS['fieldData']['len'] < strlen($baseValue)) {
                $baseValue = $this->truncateDataByLength($baseValue, (string)$GLOBALS['fieldData']['len']);
            }

            return "'" . $baseValue . "'";
        }

        if (!empty($typeData['meeting_probability'])) {
            /* If this is for meetings, and it's in the past,
             * we need to adjust the probability.
             * Note that this will break if date_start comes after
             * status in the vardefs for Meetings :-/.
             */
            if (!empty($GLOBALS['fieldData']['options'])
                && !empty($GLOBALS['app_list_strings'][$GLOBALS['fieldData']['options']])) {
                $options = $GLOBALS['app_list_strings'][$GLOBALS['fieldData']['options']];
                $keys = array_keys($options);
                /* accessLocalField loads the value of that field or
                 * computes it if it has not been computed.
                 */
                $startDate = $this->accessLocalField('date_start');

                $stamp = strtotime(substr($startDate, 1, strlen($startDate) - 2));
                if ($stamp >= $GLOBALS['baseTime']) {
                    $rn = mt_rand(0, 9);
                    /* 10% chance of being NOT HELD - aka CLOSED */
                    if ($rn > 8) {
                        $selected = 2;
                    } else {
                        $selected = 0;
                    }
                } else {
                    $rn = mt_rand(0, 49);
                    /* 2% chance of being HELD - aka OPEN */
                    if ($rn > 48) {
                        $selected = 0;
                    } else {
                        $selected = 2;
                    }
                }
            }
            return "'" . @trim($keys[$selected]) . "'";
        }

        if (isset($typeData['phone'])) {
            return "'" . Phone::getNumber() . "'";
        }

        $isQuote = true;
        //we have a range then it should either be a number or a date
        $baseValue = '';
        if (!empty($typeData['range'])) {
            $baseValue = mt_rand($typeData['range']['min'], $typeData['range']['max']);

            if (!empty($typeData['multiply'])) {
                $baseValue *= $typeData['multiply'];
            }
            //everything but numbers must have a type so we are just a range
            if (!empty($typeData['type'])) {
                $isQuote = true;

                $dateTime = new \DateTime();
                $dateTime->setTimestamp($GLOBALS['baseTime']);

                if (empty($typeData['units'])) {
                    throw new \Exception("units is not set for date/time range type field");
                }
                $dateTime->modify($baseValue . ' ' . $typeData['units']);

                // Use Sugar class to convert dateTime to $type format for saving TZ settings
                $baseValue = $GLOBALS['timedate']->asDbType($dateTime, $typeData['type']);
            } else {
                $isQuote = false;
            }
        } elseif (!empty($typeData['list']) && !empty($GLOBALS[$typeData['list']])) {
            $selected = ($this->count + mt_rand(0, 100)) % count($GLOBALS[$typeData['list']]);
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

                foreach ($typeData['modify'] as $modType => $modValue) {
                    // If value is depending on another field - let's get field value, otherwise - use value
                    if (is_array($modValue) && !empty($modValue['field'])
                        && !empty($this->fields[$modValue['field']])) {
                        $timeUnit = $this->accessLocalField($modValue['field']);
                    } else {
                        $timeUnit = $modValue;
                    }

                    $shift += $this->applyDatetimeModifications($modType, $timeUnit);
                }

                $baseValue = date('Y-m-d H:i:s', strtotime($baseValue) + $shift);
            }
        }

        if (!empty($typeData['suffixlist'])) {
            foreach ($typeData['suffixlist'] as $suffixlist) {
                $selected = ($this->count + mt_rand(0, 100)) % count($GLOBALS[$suffixlist]);
                $baseValue .= ' ' . $GLOBALS[$suffixlist][$selected];
            }
        } elseif ($type == 'enum') {
            if (!empty($GLOBALS['fieldData']['options'])
                && !empty($GLOBALS['app_list_strings'][$GLOBALS['fieldData']['options']])) {
                $value = null;
                $rnd = mt_rand(1, 100);
                foreach ($typeData['enum_key_probabilities'] as $probabilityData) {
                    if ($rnd > $probabilityData[0]) {
                        $value = $probabilityData[1];
                        break;
                    }
                }

                if (is_null($value)) {
                    throw new \Exception("Enum value was not generated for field: $field");
                }

                return "'$value'";
            }
        }

        // This is used to associate email addresses with rows in
        // Contacts or Leads.  See config/relationships/email_addr_bean_rel.php
        if (!empty($typeData['getmodule'])) {
            return "'" . $this->module . "'";
        }
        if (!empty($typeData['prefixlist'])) {
            foreach ($typeData['prefixlist'] as $prefixlist) {
                $selected = ($this->count + mt_rand(0, 100)) % count($GLOBALS[$prefixlist]);
                $baseValue = $GLOBALS[$prefixlist][$selected] . ' ' . $baseValue;
            }
        }

        if (!empty($typeData['suffix'])) {
            $baseValue .= $typeData['suffix'];
        }
        if (!empty($typeData['prefix'])) {
            $baseValue = $typeData['prefix'] . $baseValue;
        }

        if (!empty($GLOBALS['fieldData']['len']) && $GLOBALS['fieldData']['len'] < strlen($baseValue)) {
            $baseValue = $this->truncateDataByLength($baseValue, (string)$GLOBALS['fieldData']['len']);
        }

        if ($isQuote || !empty($typeData['isQuoted'])) {
            $baseValue = "'" . @trim($baseValue) . "'";
        }

        // Run db convert only for specific types. see DBManager::convert()
        if ($this->storageType != Factory::OUTPUT_TYPE_CSV
             && !in_array($type, self::$notConvertedTypes)
        ) {
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
     * Returns the value of this module's field called $fieldName.
     *
     * If a value has already been generated, it uses that one, otherwise
     * it calls generateFieldData() to generate the value.
     * @param $fieldName - Name of the local field you want to retrieve
     *
     * @return string
     */
    public function accessLocalField($fieldName)
    {
        /* We will only deal with fields defined in the
         * vardefs.
         */
        if (!empty($this->fields[$fieldName])) {
            if (!isset($this->installData[$fieldName])) {
                $this->generateFieldData($fieldName);
            }
            return $this->installData[$fieldName];
        } else {
            return $fieldName;
        }
    }

    /**
     * Returns a gibberish string based on a reordering of the base text
     * (Lorem ipsum dolor sit amet, ....)
     *
     * @param $wordCount
     * @return string
     */
    public function generateGibberish($wordCount = 1)
    {
        static $words = array();

        if (empty($words)) {
            $baseText = "Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nunc pulvinar tellus et arcu. " .
                "Integer venenatis nonummy risus. Sed turpis lorem, cursus sit amet, eleifend at, dapibus vitae, " .
                "lacus. Nunc in leo ac justo volutpat accumsan. In venenatis consectetuer ante. Proin tempus sapien " .
                "et sapien. Nunc ante turpis, bibendum sed, pharetra a, eleifend porta, augue. Curabitur et nulla. " .
                "Proin tristique erat. In non turpis. In lorem mauris, iaculis ac, feugiat sed, bibendum eu, enim. " .
                "Donec pede. Phasellus sem risus, fermentum in, imperdiet vel, mattis nec, justo. Nullam vitae " .
                "risus. Fusce neque. Mauris malesuada euismod magna. Sed nibh pede, consectetuer quis, condimentum " .
                "sit amet, pretium ut, eros. Quisque nec arcu. Sed ac neque. Maecenas volutpat erat ac est. " .
                "Nam mauris. Sed condimentum cursus purus. Integer facilisis. Duis libero ante, cursus nec, " .
                "congue nec, imperdiet et, ligula. Pellentesque porttitor suscipit nulla. Integer diam magna, " .
                "luctus rutrum, luctus sit amet, euismod a, diam. Nunc vel eros faucibus velit lobortis faucibus. " .
                "Phasellus ultrices, nisl id pulvinar fringilla, justo augue elementum enim, eget tincidunt dolor " .
                "pede et tortor. Vestibulum at justo vitae sem auctor tincidunt. Maecenas facilisis volutpat dui. " .
                "Pellentesque non justo. Quisque eleifend, tellus quis venenatis volutpat, ipsum purus cursus dolor, " .
                "in aliquam magna sem.";
            $words = explode(' ', $baseText);
        }

        shuffle($words);
        $resWords = ($wordCount > 0) ? array_slice($words, 0, $wordCount) : $words;

        return implode(' ', $resWords);
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
            case 'hours':
                $shift = 3600;
                break;
            case 'minutes':
                $shift = 60;
                break;
            case 'days':
                $shift = 24 * 3600;
        }

        // Return N(days/hours/minutes)*$shift = number of seconds to shift
        return $value * $shift;
    }

    /**
     * Get hash from field according current sugar
     * hashing settings
     *
     * @param $hashFromField
     * @return string
     */
    protected function getSameSugarHash($hashFromField)
    {
        $value = $this->accessLocalField($hashFromField);
        if (is_string($value)) {
            $value = substr($value, 1, strlen($value) - 2);
        }

        if (version_compare($GLOBALS['sugar_config']['sugar_version'], '7.7.0', '<')) {
            $password = "'" . md5($value) . "'";
        } else {
            require_once SUGAR_PATH . '/src/Security/Password/Hash.php';
            $hash = \Sugarcrm\Sugarcrm\Security\Password\Hash::getInstance();
            $password = "'" . $hash->hash($value) . "'";
        }

        return $password;
    }

    /**
     * Calculate a chance for each possible enum value using field options
     * @param array $fieldDef
     * @param array $all
     * @return array
     */
    private function calcEnumProbabilities(array $fieldDef, array $all): array
    {
        $possibleKeys = $GLOBALS['app_list_strings'][$fieldDef['options']];
        $rest = 100 - array_sum($all);
        $missed = array_diff_key($possibleKeys, $all);
        if ($rest > 0 && !empty($missed)) {
            $probabilityPerValue = round($rest / count($missed));
            foreach ($missed as $value) {
                $all[$value] = $probabilityPerValue;
            }
        }

        // transform probabilities to a [to, $value] fo further usage
        $summary = 0;
        foreach ($all as $key => $probability) {
            $all[$key] = [$summary, $key];
            $summary += $probability;
        }
        return array_reverse(array_values($all));
    }
}
