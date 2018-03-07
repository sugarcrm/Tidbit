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
 * Given an array return random array elements from the array
 *
 * @param array $array
 * @param int $num
 * @return array
 */
function get_random_array($array, $num)
{
    $rand = array_rand($array, $num);
    $result = array();

    for ($i = 0; $i < $num; $i++) {
        $result[$i] = $array[$rand[$i]];
    }
    return $result;
}

/**
 * generate_team_set
 * Helper function to recursively create team sets
 *
 * @param $primary string The primary team
 * @param $teams string The teams to use
 */
function generate_team_set($primary, $teams)
{
    if (!in_array($primary, $teams)) {
        array_push($teams, $primary);
    }
    $teams = array_reverse($teams);
    $team_count = count($teams);
    for ($i = 0; $i < $team_count; $i++) {
        /** @var TeamSet $teamSet */
        $teamSet = BeanFactory::getBean('TeamSets');
        $teamSet->addTeams($teams);
        array_pop($teams);
    }
}

function generate_full_teamset($set, $teams)
{
    $team_count = count($teams);
    for ($i = 0; $i < $team_count; $i++) {
        $teamset = new TeamSet();
        $teamset->addTeams(array_unique(array_merge($set, array($teams[$i]))));
    }
}

/**
 * Generate $tidbit_relationships array for all custom Many/Many Relationships
 *
 * @param array $tidbit_relationships exiting relationship configuration
 * @return array
 */
function generate_m2m_relationship_list($tidbit_relationships = array())
{

    $skips = array();

    global $dictionary;
    foreach ($dictionary as $module => $field_and_rel_data) {
        if (!isset($field_and_rel_data['relationships'])) {
            continue;
        }
        foreach ($field_and_rel_data['relationships'] as $rel_name => $rel_data) {
            if (!isset($rel_data['join_table'])) {
                $skips[] = $rel_name;
                continue;
            }

            $parent_module = $rel_data['lhs_module'];
            $second_module = $rel_data['rhs_module'];
            $self = $rel_data['join_key_lhs'];
            $you = $rel_data['join_key_rhs'];
            $table = $rel_data['join_table'];

            if (!isset($tidbit_relationships[$parent_module])) {
                $tidbit_relationships[$parent_module] = array();
            }

            /*
             * don't override existing definitions
             */
            if (isset($tidbit_relationships[$parent_module][$second_module])) {
                continue;
            }

            $tidbit_relationships[$parent_module][$second_module] = array(
              'self' => $self,
              'you' => $you,
              'table' => $table,
            );
        }
    }

    return $tidbit_relationships;
}

/**
 * @param string $dir
 */
function clearCsvDir($dir)
{
    $fileToDelete = glob($dir . '/*csv');
    foreach ($fileToDelete as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

/**
 * @param string $message
 */
function exitWithError($message)
{
    fwrite(STDERR, $message . PHP_EOL);
    die(1);
}

/**
 * @param string $path
 * @param string $files_pattern
 */
function includeDataInDir($path, $files_pattern = '/^[\w]+\.php$/')
{
    $entries = scandir($path);
    foreach ($entries as $entry) {
        if (preg_match($files_pattern, $entry)) {
            require_once $path . '/' . $entry;
        }
    }
}


/**
 * @param int $done
 * @param int $total
 * @param int $size
 */
function show_status($done, $total, $size = 40)
{
    $perc=(double)($done/$total);
    $bar=floor($perc*$size);

    $status_bar="\r\033[K\tHitting DB... [";
    $status_bar.=str_repeat("=", $bar);
    if ($bar<$size) {
        $status_bar.=">";
        $status_bar.=str_repeat(" ", $size-$bar);
    } else {
        $status_bar.="=";
    }
    $disp=number_format($perc*100, 0);
    $status_bar.="] $disp%  $done/$total";
    echo "$status_bar";
}
