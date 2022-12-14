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

use Sugarcrm\Tidbit\InsertBuffer;
use Sugarcrm\Tidbit\StorageAdapter\Storage\Csv;

class ForkingController
{
    /**
     * Generatro
     *
     * @var Generator
     */
    protected $g;

    /**
     * Threads count
     *
     * @var int
     */
    protected $threads;

    protected $progressLogPrefix;

    public function __construct(Generator $g, $threads)
    {
        $this->g = $g;
        $this->threads = $threads;
    }

    public function setProgressLogPrefix($progressLogPrefix)
    {
        $this->progressLogPrefix = $progressLogPrefix;
    }

    public function generate($total)
    {
        $GLOBALS['db']->disconnect();
        $pids = [];
        for ($i = 0; $i < $this->threads; $i++) {
            $pid = \pcntl_fork();
            if ($pid == -1) {
                throw new Exception("Could not fork");
            } elseif ($pid) {
                $pids[] = $pid;
            } else {
                $GLOBALS['db']->connect();
                $chunk = round($total / $this->threads);
                if ($GLOBALS['storageAdapter'] instanceof Csv) {
                    $GLOBALS['storageAdapter']->setFilenameSuffix('.'.($i + 1));
                }
                $this->doGenerate($i * $chunk, min(($i + 1) * $chunk, $total), $i);
                exit(0);
            }
        }

        foreach ($pids as $pid) {
            \pcntl_waitpid($pid, $status);
            if ($status != 0) {
                throw new Exception("Child process $pid failed with status code $status");
            }
        }

        foreach ($pids as $pid) {
            echo "\tChild process $pid exited\n";
        }
        $GLOBALS['db']->connect();
    }

    protected function doGenerate($from, $to, $thread)
    {
        $buffers = [];
        $this->showProgress($thread, $from, $from, $to);
        $t = microtime(true);
        for ($i = $from; $i < $to; $i++) {
            $data = $this->g->generateRecord($i);

            $GLOBALS['processedRecords']++;
            foreach ($data['data'] as $table => $rows) {
                if (!isset($buffers[$table])) {
                    $buffers[$table] = new InsertBuffer($table, $GLOBALS['storageAdapter']);
                }

                foreach ($rows as $row) {
                    $buffers[$table]->addInstallData($row);
                    $GLOBALS['allProcessedRecords']++;
                }
            }

            if (microtime(true) - $t > 15) {
                $this->showProgress($thread, $from, $i, $to);
                $t = microtime(true);
            }
        }

        foreach ($buffers as $buffer) {
            $buffer->flush();
        }

        $this->showProgress($thread, $from, $to, $to);
    }

    protected function showProgress($thread, $from, $i, $to)
    {
        $toMFrom = ($to-$from);
        printf(
            "\t{$this->progressLogPrefix} [%3d] %d/%d (%d%%) [%d:%d)\n",
            $thread+1,
            $i-$from,
            $to-$from,
            ($i-$from)/($toMFrom == 0 ? 1 : $toMFrom)*100,
            $from,
            $to
        );
    }
}
