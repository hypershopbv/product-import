<?php

namespace BigBridge\ProductImport\Test;

use Magento\Catalog\Api\ProductRepositoryInterface;
use BigBridge\ProductImport\Model\Data\SimpleProduct;
use BigBridge\ProductImport\Model\ImportConfig;
use BigBridge\ProductImport\Model\ImporterFactory;

/**
 * Integration test
 *
 * @author Patrick van Bergen
 */
class ImportTest extends \PHPUnit_Framework_TestCase
{
    public function testInsert()
    {
        require __DIR__ . '/../../../../index.php';

        $om = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var ImporterFactory $factory */
        $factory = $om->get(ImporterFactory::class);

        /** @var ProductRepositoryInterface $repository */
        $repository = $om->get(ProductRepositoryInterface::class);

        $config = new ImportConfig();
#todo: not up to the user
        $config->batchSize = 200;
        $config->eavAttributes = ['name', 'price'];

        $importer = $factory->create($config);

        $sku1 = uniqid("bb");
        $sku2 = uniqid("bb");
        $sku3 = uniqid("bb");
        $sku4 = uniqid("bb");
        $sku5 = uniqid("bb");
        $sku6 = uniqid("bb");

        $products = [
            ["Big Blue Box", $sku1, 'Default', '3.25'],
            ["Big Yellow Box", null, 'Default', '4.00'],
            ["Big Red Box", $sku2, 'Default', '127.95'],
            [null, ' ', "\n", null],
            ["Big Blue Box", $sku3, 'Boxes', '11.45'],
            ["Big Orange Box", $sku4, 'Default', '11,45'],
            ["Big Pink Box", $sku5, 'Default', 11.45],
            ["Big Turquoise Box", $sku5, 'Default', new \SimpleXMLElement("<xml></xml>")],
            // extra whitespace
            [" Big Empty Box ", " " . $sku6 . " ", ' Default ', ' 127.95 '],
        ];

        $results = [];

        foreach ($products as $data) {
            $product = new SimpleProduct();
            $product->name = $data[0];
            $product->sku = $data[1];
            $product->attributeSetName = $data[2];
            $product->price = $data[3];

            list($ok, $error) = $importer->insert($product);

            $results[] = [$ok, $error];
        }

        $importer->flush();

        $product1 = $repository->get($sku1);
        $this->assertTrue($product1->getAttributeSetId() > 0);
        $this->assertEquals($products[0][0], $product1->getName());
        $this->assertEquals($products[0][3], $product1->getPrice());

        $product2 = $repository->get($sku2);
        $this->assertTrue($product2->getAttributeSetId() > 0);
        $this->assertEquals($products[2][0], $product2->getName());
        $this->assertEquals($products[2][3], $product2->getPrice());

        $product6 = $repository->get(trim($sku6));
        $this->assertTrue($product6->getAttributeSetId() > 0);
        $this->assertEquals(trim($products[8][0]), $product6->getName());
        $this->assertEquals(trim($products[8][3]), $product6->getPrice());

        $expected = [
            [true, ""],
            [false, "missing sku"],
            [true, ""],
            [false, "missing sku; missing attribute set name; missing name; missing price"],
            [false, "unknown attribute set name: Boxes"],
            [false, "price is not a decimal number (11,45)"],
            [false, "price is a double, should be a string"],
            [false, "price is an object (SimpleXMLElement), should be a string"],
            [true, ""],
        ];

        $this->assertEquals($expected, $results);
    }

    public function testUpdate()
    {
        require __DIR__ . '/../../../../index.php';

        $om = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var ImporterFactory $factory */
        $factory = $om->get(ImporterFactory::class);

        /** @var ProductRepositoryInterface $repository */
        $repository = $om->get(ProductRepositoryInterface::class);

        $config = new ImportConfig();
        $config->eavAttributes = ['name', 'price'];

        $importer = $factory->create($config);

        $sku1 = uniqid("bb");
        $sku2 = uniqid("bb");

        $products = [
            ["Big Blue Box", $sku1, 'Default', '3.25'],
            ["Big Yellow Box", $sku2, 'Default', '4.00'],
        ];

        $results = [];

        foreach ($products as $data) {
            $product = new SimpleProduct();
            $product->name = $data[0];
            $product->sku = $data[1];
            $product->attributeSetName = $data[2];
            $product->price = $data[3];

            list($ok, $error) = $importer->insert($product);

            $results[] = [$ok, $error];
        }

        $importer->flush();

        $products2 = [
            ["Big Blueish Box", $sku1, 'Default', '3.45'],
            ["Big Yellowish Box", $sku2, 'Default', '3.95'],
        ];

        foreach ($products2 as $data) {
            $product = new SimpleProduct();
            $product->name = $data[0];
            $product->sku = $data[1];
            $product->attributeSetName = $data[2];
            $product->price = $data[3];

            list($ok, $error) = $importer->insert($product);

            $results[] = [$ok, $error];
        }

        $importer->flush();

        $product1 = $repository->get($sku1);
        $this->assertTrue($product1->getAttributeSetId() > 0);
        $this->assertEquals($products2[0][0], $product1->getName());
        $this->assertEquals($products2[0][3], $product1->getPrice());

        $product2 = $repository->get($sku2);
        $this->assertTrue($product2->getAttributeSetId() > 0);
        $this->assertEquals($products2[1][0], $product2->getName());
        $this->assertEquals($products2[1][3], $product2->getPrice());
    }
}