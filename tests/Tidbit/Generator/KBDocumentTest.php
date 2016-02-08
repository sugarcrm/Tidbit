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


require_once dirname(__FILE__) . '/../../config.php';
require_once 'Tidbit/Generator/KBDocument.php';
Mock::generate('KBDocument');
Mock::generate('KBDocumentRevision');
Mock::generate('KBContent');
Mock::generate('TimeDate');

class Tidbit_Generator_KBDocumentTest extends UnitTestCase
{
    private $_doc, $_rev, $_content;

    private $_valid_statuses = array('Draft', 'In Review', 'Published');

    public function setup()
    {
        $mockTimedate = new MockTimeDate();
        $timedate = Tidbit_Registry::instance()->timedate = $mockTimedate;
        $this->_doc = new MockKBDocument();
        $this->_rev = new MockKBDocumentRevision();
        $this->_content = new MockKBContent();
    }


    public function testRequiresAValidDocumentDocumentrevisionAndContentObject()
    {
        $gen = new Tidbit_Generator_KBDocument($this->_doc, $this->_rev, $this->_content);
        // todo test with reflections - its more certain
    }

    public function testCallsSaveOnPassedInDocumentXNumberOfTimesBasedOnNumberPassedToGenerate()
    {
        $this->_doc->expectOnce('save');
        $this->_rev->expectOnce('save');
        $this->_content->expectOnce('save');
        $gen = new Tidbit_Generator_KBDocument($this->_doc, $this->_rev, $this->_content);
        $gen->generate(1);
        unset($gen);

        $random = rand(10, 20);
        $newDoc = new MockKBDocument();
        $newDoc->expectCallCount('save', $random);
        $newRev = new MockKBDocumentRevision();
        $newRev->expectCallCount('save', $random);
        $newContent = new MockKBContent();
        $newContent->expectCallCount('save', $random);
        $gen = new Tidbit_Generator_KBDocument($newDoc, $newRev, $newContent);
        $gen->generate($random);
    }

    public function testTheObjectsPassedInWillHaveTheMostRecentlyInsertedDataContainedInThem()
    {
        $gen = new Tidbit_Generator_KBDocument($this->_doc, $this->_rev, $this->_content);
        $gen->generate(1);

        $this->assertTrue($this->_doc->new_with_id);
        $this->assertWantedPattern('/^seed-/', $this->_doc->id);
        $this->assertIdentical(0, $this->_doc->is_external);
        $this->assertTrue(in_array($this->_doc->status_id, $this->_valid_statuses));
        $this->assertIdentical($this->_doc->id, $this->_rev->kbdocument_id);

        $this->assertWantedPattern('/^seed-.*/', $this->_content->id);
        $this->assertIdentical($this->_content->id, $this->_rev->kbcontent_id);
    }

    public function testRevisionIsMarkedAsLatest()
    {
        $gen = new Tidbit_Generator_KBDocument($this->_doc, $this->_rev, $this->_content);
        $gen->generate(1);

        $this->assertIdentical(1, $this->_rev->latest);
    }

    public function testAllGeneratedContentUsesItsOwnIdWithPrefix()
    {
        $gen = new Tidbit_Generator_KBDocument($this->_doc, $this->_rev, $this->_content);
        $gen->generate(1);

        $this->assertTrue($this->_doc->new_with_id);
        $this->assertTrue($this->_rev->new_with_id);
        $this->assertTrue($this->_content->new_with_id);

        $this->assertWantedPattern('/^seed-.*/', $this->_doc->id);
        $this->assertWantedPattern('/^seed-.*/', $this->_rev->id);
        $this->assertWantedPattern('/^seed-.*/', $this->_content->id);
    }

    public function testCreatesSomeSortOfGibberishForContentBetweenOneAndTwoThousandWordsLong()
    {
        $gen = new Tidbit_Generator_KBDocument($this->_doc, $this->_rev, $this->_content);

        for ($i = 0; $i < 100; $i++) {
            $gen->generate(1);

            $content = $this->_content->kbdocument_body;
            $word_count = count(explode(' ', $content));
            $this->assertTrue($word_count >= 1000 && $word_count <= 2000,
                "Word count of [{$word_count}] is outside range on pass {$i}");
        }
    }

    public function testEachDocumentHasARandomStatus()
    {
        $gen = new Tidbit_Generator_KBDocument($this->_doc, $this->_rev, $this->_content);

        $found = array();
        for ($i = 0; $i < 100; $i++) {
            $gen->generate(1);
            if (!isset($found[$this->_doc->status_id])) {
                $found[$this->_doc->status_id] = true;
            }

            if (count($found) == count($this->_valid_statuses)) {
                break;
            }
        }
        $this->assertTrue($i < 100, 'Assume that all three ids will be randomly generated in less than 100 tries');
    }

    public function testWillUseRegistrysTimeDateObjectForGeneratingTime()
    {
        $mock = new MockTimeDate();
        $mock->expectAtLeastOnce('to_display_date');
        $mock->expectArguments('to_display_date', array(gmdate("Y-m-d"), false));

        $random = "Random #: " . rand(100, 200);
        $mock->setReturnValue('to_display_date', $random);
        Tidbit_Registry::instance()->timedate = $mock;

        $gen = new Tidbit_Generator_KBDocument($this->_doc, $this->_rev, $this->_content);
        $gen->generate(1);

        $this->assertEqual($random, $this->_doc->active_date);
    }

    public function testHasThreeToTenRandomWordsUsedAsDocumentName()
    {
        $gen = new Tidbit_Generator_KBDocument($this->_doc, $this->_rev, $this->_content);

        for ($i = 0; $i < 10; $i++) {
            $gen->generate(1);
            $word_count = count(explode(' ', $this->_doc->kbdocument_name));
            $this->assertTrue($word_count >= 3 && $word_count <= 10);
        }
    }

}
