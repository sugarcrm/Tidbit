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

require_once 'modules/KBDocuments/KBDocument.php';
require_once 'modules/KBDocumentRevisions/KBDocumentRevision.php';
require_once 'modules/KBContents/KBContent.php';
require_once 'Tidbit/Gibberish.php';
require_once 'Tidbit/Registry.php';

class Tidbit_Generator_KBDocument
{
    private $_doc = null;
    private $_rev = null;
    private $_content = null;
    
    private $_valid_statuses = array('Draft', 'In Review', 'Published');
    
    public function __construct(KBDocument $doc, KBDocumentRevision $rev, KBContent $content)
    {
        $this->_doc = $doc;
        $this->_rev = $rev;
        $this->_content = $content;
    }
    
    public function generate($number)
    {
        for ($i = 0; $i < $number; $i++) {
            $kb_id = 'seed-' . create_guid();
            $this->_rev->id = 'seed-' . create_guid();
            $this->_rev->latest = 1;
            $this->_content->id = 'seed-' . create_guid();
            $this->_rev->kbcontent_id = $this->_content->id;
            
            $this->_doc->id = $kb_id;
            $this->_doc->active_date = Tidbit_Registry::instance()->timedate->to_display_date(gmdate("Y-m-d"), false);
            $this->_doc->kbdocument_name = (string)new Tidbit_Gibberish(rand(3, 10));
            $this->_rev->kbdocument_id = $kb_id;
            $this->_doc->is_external = 0;
            $this->_doc->new_with_id = true;
            $this->_rev->new_with_id = true;
            $this->_content->new_with_id = true;
            $this->_doc->status_id = $this->_valid_statuses[rand(0, count($this->_valid_statuses) - 1)];
            $this->_doc->save();
            $this->_rev->save();
            
            $this->_content->kbdocument_body = (string)new Tidbit_Gibberish(rand(1000, 2000));
            $this->_content->save();
        }
    }
}
