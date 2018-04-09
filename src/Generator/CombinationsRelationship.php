<?php

namespace Sugarcrm\Tidbit\Generator;

// use \Sugarcrm\Tidbit\Core\Intervals;
use \Sugarcrm\Tidbit\Core\Factory;
use \Sugarcrm\Tidbit\DataTool;

class CombinationsRelationship extends Decorator
{
    protected $config;

    protected $idGenerator;

    protected $dataTool;

    protected $relationships;

    public function __construct(Generator $g, array $config)
    {
        parent::__construct($g);
        $this->idGenerator = Factory::getComponent('intervals');
        $this->dataTool = new \Sugarcrm\Tidbit\DataTool($GLOBALS['storageType']);
        $this->relationships = Factory::getComponent('relationships');
        $this->config = $config;

        $selfModule = $this->bean()->getModuleName();
        $youModule = $this->config['you_module'];
        $selfTotal = $this->config['self_total'];
        $youTotal = $this->config['you_total'];

        $maxAllowedDegree = $youTotal - 1;
        if ($this->config['degree'] > $maxAllowedDegree) {
            $this->config['degree'] = $maxAllowedDegree;
            /**
             * The degree is configured too high, there are not enough records in '$youTotal'
             * to satisfy this degree of relationship.
             */
            echo "WARNING: Decreasing the degree of $selfModule <-> $youModule relationship to $maxAllowedDegree\n";
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
        $maxPossibleCombinations = pow(2, $this->config['degree'] - 1);
        $youBaseN = floor($n * $youTotal / $selfTotal);
        $baseN = floor($youBaseN * $selfTotal / $youTotal);
        $combID = $maxPossibleCombinations - ($n - $baseN) - 1;

        $relatedNs = [ $youBaseN ];
        for ($i = 0; $i < $this->config['degree'] - 1; $i++) {
            $mask = 1 << $i;
            if ($combID & $mask) {
                $relatedNs[] = ($youBaseN + $i + 1) % $youTotal;
            }
        }

        $table = $this->config['table'];
        foreach ($relatedNs as $relatedN) {
            $data['data'][$table][] = [
                'id' => "'" . $this->relationships->generateRelID($table) . "'",
                $this->config['self'] => $this->idGenerator->generateTidbitID($n, $selfModule),
                $this->config['you'] => $this->idGenerator->generateTidbitID($relatedN, $youModule),
                'deleted' => 0,
                'date_modified' => $this->dataTool->getConvertDatetime(),
            ];
        }

        return $data;
    }
}
