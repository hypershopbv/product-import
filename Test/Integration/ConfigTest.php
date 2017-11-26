<?php

namespace BigBridge\ProductImport\Test\Integration;

use Magento\Framework\App\ObjectManager;
use BigBridge\ProductImport\Api\ImportConfig;
use BigBridge\ProductImport\Api\ImporterFactory;

/**
 * @author Patrick van Bergen
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ImporterFactory */
    private static $factory;

    public static function setUpBeforeClass()
    {
        // include Magento
        require_once __DIR__ . '/../../../../../index.php';

        /** @var ImporterFactory $factory */
        self::$factory = ObjectManager::getInstance()->get(ImporterFactory::class);
    }

    public function testConfig()
    {
        $config = new ImportConfig();

        list($importer, $error) = self::$factory->createImporter($config);

        $this->assertNotNull($importer);
        $this->assertEquals("", $error);

        // ---

        $config = new ImportConfig();
        $config->batchSize = 0;

        list($importer, $error) = self::$factory->createImporter($config);

        $this->assertNull($importer);
        $this->assertEquals("config: batchSize should be 1 or more", $error);

        // ---

        $config = new ImportConfig();
        $config->batchSize = "1000";

        list($importer, $error) = self::$factory->createImporter($config);

        $this->assertNull($importer);
        $this->assertEquals("config: batchSize is not an integer", $error);


        // ---

        $config = new ImportConfig();
        $config->resultCallbacks = function() {};

        list($importer, $error) = self::$factory->createImporter($config);

        $this->assertNull($importer);
        $this->assertEquals("config: resultCallbacks should be an array of functions", $error);

        // ---

        $config = new ImportConfig();

        list($importer, $error) = self::$factory->createImporter($config);

        // $config has copied, the original is unchanged
        $this->assertEquals(null, $config->magentoVersion);

        // ---

        $config = new ImportConfig();
        $config->magentoVersion = '2';

        list($importer, $error) = self::$factory->createImporter($config);

        $this->assertEquals("config: invalid Magento version number", $error);
    }
}