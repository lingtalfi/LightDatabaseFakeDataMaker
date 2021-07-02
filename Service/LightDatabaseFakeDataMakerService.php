<?php


namespace Ling\Light_DatabaseFakeDataMaker\Service;


use Ling\Light\ServiceContainer\LightServiceContainerInterface;
use Ling\Light_Database\Service\LightDatabaseService;
use Ling\Light_DatabaseFakeDataMaker\Exception\LightDatabaseFakeDataMakerException;
use Ling\Light_DatabaseFakeDataMaker\Generator\LightDatabaseFakeDataGeneratorInterface;
use Ling\Light_SqlWizard\Service\LightSqlWizardService;


/**
 * The LightDatabaseFakeDataMakerService class.
 */
class LightDatabaseFakeDataMakerService
{

    /**
     * This property holds the container for this instance.
     * @var LightServiceContainerInterface
     */
    protected LightServiceContainerInterface $container;

    /**
     * This property holds the options for this instance.
     *
     * Available options are:
     *
     *
     *
     * See the @page(Light_DatabaseFakeDataMaker conception notes) for more details.
     *
     *
     * @var array
     */
    protected $options;


    /**
     * Builds the LightDatabaseFakeDataMakerService instance.
     */
    public function __construct()
    {
        $this->options = [];
    }

    /**
     * Sets the container.
     *
     * @param LightServiceContainerInterface $container
     */
    public function setContainer(LightServiceContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Sets the options.
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Returns the option value corresponding to the given key.
     * If the option is not found, the return depends on the throwEx flag:
     *
     * - if set to true, an exception is thrown
     * - if set to false, the default value is returned
     *
     *
     * @param string $key
     * @param null $default
     * @param bool $throwEx
     * @throws \Exception
     */
    public function getOption(string $key, $default = null, bool $throwEx = false)
    {
        if (true === array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }
        if (true === $throwEx) {
            $this->error("Undefined option: $key.");
        }
        return $default;
    }


    /**
     * Generate $nbRows rows into the given table, using the given generator, and returns an array of inserted data.
     *
     * The returned array is an array of index => insertedData,
     *
     * where:
     *
     * - index is a self auto-incremented index
     * - insertedData is either the array of inserted data, or an exception object
     *
     *
     * The table is given as a @page(full table).
     *
     * If a generator is not defined for a particular column, a default value will be inserted.
     *
     *
     * Available options are:
     * - stopOnException: bool=false. If true, this method stops as soon as it encounters an exception, and it throws the exception at you.
     *          If false, the exception is silently ignored, and the exception instance is available in the return of this method.
     *          For instance, if a duplicate entry is detected, this will throw an exception depending on your pdo settings (we use Ling.Light_Database under the hood).
     *
     *
     *
     *
     *
     *
     *
     * @param string $fullTable
     * @param int $nbRows
     * @param LightDatabaseFakeDataGeneratorInterface $generator
     * @param array $options
     * @return array
     */
    public function generate(string $fullTable, int $nbRows, LightDatabaseFakeDataGeneratorInterface $generator, array $options = []): array
    {

        $ret = [];
        $stopOnException = $options['stopOnException'] ?? false;

        if ($nbRows < 1) {
            $this->error("nbRows can't be less than one.");
        }


        /**
         * @var $_wi LightSqlWizardService
         */
        $_wi = $this->container->get("sql_wizard");
        /**
         * @var $_da LightDatabaseService
         */
        $_da = $this->container->get("database");

        $wiz = $_wi->getMysqlWizard();
        $columns = $wiz->getColumnDefaultApiValues($fullTable);


        for ($i = 1; $i <= $nbRows; $i++) {

            try {
                $allColumns = [];
                foreach ($columns as $column => $value) {
                    $gen = $generator->getColumnGenerator($column);
                    if (null !== $gen) {
                        if (true === is_string($gen)) {
                            $value = $gen;
                        } elseif (true === is_array($gen)) {
                            $randomKey = array_rand($gen);
                            $value = $gen[$randomKey];
                        } elseif (true === is_callable($gen)) {
                            $value = $gen($i);
                        }
                    }
                    $allColumns[$column] = $value;
                }
                $_da->insert($fullTable, $allColumns);


                $ret[$i] = $allColumns;


            } catch (\Exception $e) {
                if (true === $stopOnException) {
                    throw $e;
                }
                $ret[$i] = $e;
            }
        }
        return $ret;
    }


    //--------------------------------------------
    //
    //--------------------------------------------
    /**
     * Throws an exception.
     *
     * @param string $msg
     * @throws \Exception
     */
    private function error(string $msg)
    {
        throw new LightDatabaseFakeDataMakerException($msg);
    }

}