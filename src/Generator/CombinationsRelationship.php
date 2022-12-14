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

namespace Sugarcrm\Tidbit\Generator;

// use \Sugarcrm\Tidbit\Core\Intervals;
use \Sugarcrm\Tidbit\Core\Factory;
use Sugarcrm\Tidbit\Core\Relationships;

class CombinationsRelationship extends Decorator
{
    protected $config;

    protected $idGenerator;

    protected $currentDateTime;

    public function __construct(Generator $g, array $config)
    {
        parent::__construct($g);
        $this->idGenerator = Factory::getComponent('intervals');
        $this->config = $config;
        $this->currentDateTime = "'" . date('Y-m-d H:i:s') . "'";

        $selfModule = $this->bean()->getModuleName();
        $youModule = $this->config['you_module'];
        $selfTotal = $this->config['self_total'];
        $youTotal = $this->config['you_total'];

        if ($this->config['degree'] * 2 > $youTotal) {
            /**
             * The degree can't be higher than $youTotal/2 because otherwise in the case of high
             * enough number of $selfTotal records there will be duplidate combinations generated
             */
            echo "ERROR: $selfModule <-> $youModule relationship: The degree can't be higher than $youModule/2 " .
                "because otherwise in the case of high enough number of $selfModule records " .
                "duplidate combinations will be generated.\n";
        }

        /*
         * verify that with the given config it's possible to generate
         * enough amount of unique combinations
         *
         * bucket - all 'self' records that relate to the same 'you' base record
         */
        $maxBucketSize = ceil($selfTotal / $youTotal);
        $maxPossibleCombinations = pow(2, $this->config['degree'] - 1);
        if ($maxBucketSize > $maxPossibleCombinations) {
            echo "ERROR: $selfModule <-> $youModule relationship: Either decrese the amount of $selfModule records " .
                "or increase the amount of $youModule, or increase the degree of this relationship.\n";
        }
    }

    public function generateRecord($n)
    {
        $data = parent::generateRecord($n);

        $selfModule = $this->bean()->getModuleName();
        $youModule = $this->config['you_module'];

        $selfTotal = $this->config['self_total'];
        $youTotal = $this->config['you_total'];
        $degree = $this->config['degree'];

        $relatedNs = CombinationsHelper::get($n, $degree, $selfTotal, $youTotal);

        $table = $this->config['table'];
        foreach ($relatedNs as $relatedN) {
            $data['data'][$table][] = [
                'id' => "'" . $this->relsGen()->generateRelID($n, $youModule, $relatedN, 0, 0) . "'",
                $this->config['self'] => $this->idGenerator->generateTidbitID($n, $selfModule),
                $this->config['you'] => $this->idGenerator->generateTidbitID($relatedN, $youModule),
                'deleted' => 0,
                'date_modified' => $this->currentDateTime,
            ];
        }

        return $data;
    }
}
