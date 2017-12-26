<?php

namespace BigBridge\ProductImport\Api;

use BigBridge\ProductImport\Model\Resource\MetaData;
use BigBridge\ProductImport\Model\Resource\Serialize\JsonValueSerializer;
use BigBridge\ProductImport\Model\Resource\Serialize\SerializeValueSerializer;
use BigBridge\ProductImport\Model\Resource\Serialize\ValueSerializer;
use Magento\Framework\App\ObjectManager;
use Exception;

/**
 * @author Patrick van Bergen
 */
class ImporterFactory
{
    /** @var  MetaData */
    protected $metaData;

    public function __construct(MetaData $metaData)
    {
        $this->metaData = $metaData;
    }

    /**
     * Creates an importer based on a given configuration.
     *
     * Note: the config object is copied; making changes to it later does not affect the importer.
     *
     * @param ImportConfig $originalConfig
     * @return Importer
     * @throws Exception
     */
    public function createImporter(ImportConfig $originalConfig)
    {
        // disallow changing the config after import creation; it could cause all kinds of trouble
        $config = clone $originalConfig;

        $this->fillInDefaults($config);

        $this->validateConfig($config);

        $valueSerializer = $this->createValueSerializer($config);

        $om = ObjectManager::getInstance();
        $importer = $om->create(Importer::class, ['config' => $config, 'valueSerializer' => $valueSerializer]);

        return $importer;
    }

    /**
     * @param ImportConfig $config
     * @throws Exception
     */
    protected function fillInDefaults(ImportConfig $config)
    {
        if (is_null($config->magentoVersion)) {

            // Note: this is the official version to determine the Magento version:
            //
            // $productMetadata = new \Magento\Framework\App\ProductMetadata();
            // $version = $productMetadata->getVersion();
            //
            // But is takes 0.2 seconds to execute, this is too long
            // See also https://magento.stackexchange.com/questions/96858/how-to-get-magento-version-in-magento2-equivalent-of-magegetversion

            if (preg_match('/"version": "([^\"]+)"/', file_get_contents(BP . '/vendor/magento/magento2-base/composer.json'), $matches)) {
                $config->magentoVersion = $matches[1];
            } else {
                throw new Exception("Magento version could not be detected.");
            }
        }
    }

    protected function createValueSerializer(ImportConfig $config): ValueSerializer
    {
        if (version_compare($config->magentoVersion, '2.2.0') >= 0) {
            return new JsonValueSerializer();
        } else {
            return new SerializeValueSerializer();
        }
    }

    /**
     * @param ImportConfig $config
     * @throws Exception
     */
    protected function validateConfig(ImportConfig $config)
    {
        if (!preg_match('/^2\.\d/', $config->magentoVersion)) {
            throw new Exception("config: invalid Magento version number");
        }

        if (!is_integer($config->batchSize)) {
            throw new Exception("config: batchSize is not an integer");
        } else if ($config->batchSize <= 0) {
            throw new Exception("config: batchSize should be 1 or more");
        } elseif (!is_array($config->resultCallbacks)) {
            throw new Exception("config: resultCallbacks should be an array of functions");
        }
    }
}