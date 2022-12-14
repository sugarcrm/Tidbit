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

namespace Sugarcrm\Tidbit\Generator;

use \Sugarcrm\Tidbit\Core\Factory;

class RevenueLineItemsGenerator extends ModuleGenerator
{
    protected $idGenerator;
    protected $currentDateTime;
    protected $timestampCache = [];

    private $dateClosedFormat = 'Y-m-d';

    public function __construct(\SugarBean $bean)
    {
        parent::__construct($bean);
        $this->currentDateTime = "'" . date('Y-m-d H:i:s') . "'";
        $this->idGenerator = Factory::getComponent('intervals');
    }

    public function clean()
    {
        parent::clean();
        $GLOBALS['db']->query("DELETE FROM forecast_worksheets WHERE id LIKE 'seed-%'", true);
    }

    public function generateRecord($n)
    {
        $data = parent::generateRecord($n);
        $rliData = $data['data']['revenue_line_items'][0];
        $rliData['date_closed_timestamp'] = "'" . $this->getTimestampFromDateByFormat(
            trim($rliData['date_closed'], "'to_date()YMD- ,"),
            $this->dateClosedFormat
        ) . "'";
        $data['data']['revenue_line_items'][0]['date_closed_timestamp'] =
            $rliData['date_closed_timestamp'];

        $data['data']['forecast_worksheets'][] = [
            'id' => $this->idGenerator->generateTidbitID($n, 'ForWS'),
            'parent_id' => $rliData['id'],
            'parent_type' => "'RevenueLineItems'",
            'deleted' => 0,
            'date_modified' => $rliData['date_modified'] ?? "'$this->currentDateTime'",
            'modified_user_id' => $rliData['created_by'] ?? "''",
            'account_id' => $rliData['account_id'] ?? "''",
            'account_name' => $rliData['account_name'] ?? "''",
            'name' => $rliData['name'] ?? "''",
            'likely_case' => $rliData['likely_case'] ?? "''",
            'best_case' => $rliData['best_case'] ?? "''",
            'base_rate' => $rliData['base_rate'] ?? "''",
            'worst_case' => $rliData['worst_case'] ?? "''",
            'currency_id' => $rliData['currency_id'] ?? "''",
            'date_closed' => $rliData['date_closed'] ?? "''",
            'date_closed_timestamp' => $rliData['date_closed_timestamp'] ?? "''",
            'probability' => $rliData['probability'] ?? "''",
            'commit_stage' => $rliData['probability'] >= 70 ? "'include'" : "'exclude'",
            'sales_stage' => $rliData['sales_stage'] ?? "''",
            'assigned_user_id' => $rliData['assigned_user_id'] ?? "''",
            'created_by' => $rliData['created_by'] ?? "''",
            'date_entered' => $rliData['date_entered'] ?? "''",
            'deleted' => $rliData['deleted'] ?? "''",
            'team_id' => $rliData['team_id'] ?? "''",
            'team_set_id' => $rliData['team_set_id'] ?? "''",
            'opportunity_id' => $rliData['opportunity_id'] ?? "''",
            'opportunity_name' => $rliData['opportunity_name'] ?? "''",
            'description' => $rliData['description'] ?? "''",
            'next_step' => $rliData['next_step'] ?? "''",
            'lead_source' => $rliData['lead_source'] ?? "''",
            'product_type' => $rliData['product_type'] ?? "''",
            'campaign_id' => $rliData['campaign_id'] ?? "''",
            'campaign_name' => $rliData['campaign_name'] ?? "''",
            'product_template_id' => $rliData['product_template_id'] ?? "''",
            'product_template_name' => $rliData['product_template_name'] ?? "''",
            'category_id' => $rliData['category_id'] ?? "''",
            'category_name' => $rliData['category_name'] ?? "''",
            'list_price' => $rliData['list_price'] ?? "''",
            'cost_price' => $rliData['cost_price'] ?? "''",
            'discount_price' => $rliData['discount_price'] ?? "''",
            'discount_amount' => $rliData['discount_amount'] ?? "''",
            'quantity' => (int)$rliData['quantity'] ?? "''",
            'total_amount' => $rliData['total_amount'] ?? "''",
            'draft' => 1,
        ];

        return $data;
    }

    protected function getDateTimeByFormat($date, $format)
    {
        return \DateTime::createFromFormat($format, $date);
    }

    protected function getTimestampFromDateByFormat($date, $format)
    {
        return $this->getDateTimeByFormat($date, $format)->getTimestamp();
    }
}
