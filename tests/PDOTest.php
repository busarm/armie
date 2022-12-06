<?php

namespace Busarm\PhpMini\Test;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Test\TestApp\Repositories\ProductTestRepository;
use PHPUnit\Framework\TestCase;
use Busarm\PhpMini\Test\TestApp\Models\CategoryTestModel;
use Busarm\PhpMini\Test\TestApp\Models\ProductTestModel;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @covers \Busarm\PhpMini\Data\PDO\Connection
 * @covers \Busarm\PhpMini\Data\PDO\ConnectionConfig
 * @covers \Busarm\PhpMini\Data\PDO\Model
 * @covers \Busarm\PhpMini\Data\PDO\Repository
 * @group skip
 * @group pdo
 */
final class PDOTest extends TestCase
{
    private static App|null $app = NULL;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        if (!isset(self::$app)) {
            $config = (new Config())
                ->setAppPath(__DIR__ . '/TestApp')
                ->setConfigPath('Configs')
                ->setViewPath('Views')
                ->setPdoConnectionDriver("mysql")
                ->setPdoConnectionHost("localhost")
                ->setPdoConnectionDatabase('default')
                ->setPdoConnectionPort(3306)
                ->setPdoConnectionUsername("root")
                ->setPdoConnectionPassword("root")
                ->setPdoConnectionPersist(false)
                ->setPdoConnectionErrorMode(true);
            self::$app = new App($config);
        }
    }

    /**
     * Test create product with model
     *
     * @group pdo-edit
     * @return void
     */
    public function testCreateProductModel()
    {
        $productModel = new ProductTestModel();
        $productModel->load([
            'name' => "IPhone 13",
            'type' => "Global",
            'qty' => 5,
        ]);
        $result = $productModel->save();
        $this->assertNotNull($result);
        $this->assertNotEquals(false, $result);
    }

    /**
     * Test create product with model
     *
     * @group pdo-edit
     * @return void
     */
    public function testUpdateProductModel()
    {
        $productModel = new ProductTestModel();
        $productModel->load([
            'name' => "IPhone 14",
            'type' => "Global",
            'qty' => 10,
        ]);
        $result = $productModel->save();
        $this->assertNotNull($result);
        $this->assertNotEquals(false, $result);

        $productModel->load([
            'type' => "China",
            'qty' => 15,
        ]);
        $result = $productModel->save();
        $this->assertNotNull($result);
        $this->assertNotEquals(false, $result);
    }

    /**
     * Test create product with repo
     *
     * @group pdo-edit
     * @return void
     */
    public function testCreateProductRepo()
    {
        $productRepo = new ProductTestRepository();
        $result = $productRepo->create([
            'name' => "IPhone 14",
            'type' => "Space Gray",
            'qty' => 10,
        ]);
        $this->assertNotNull($result);
    }

    /**
     * Test create product with repo
     *
     * @group pdo-edit
     * @return void
     */
    public function testUpdateProductRepo()
    {
        $productRepo = new ProductTestRepository();
        $result = $productRepo->create([
            'name' => "IPhone 14",
            'type' => "Space Gray",
            'qty' => 10,
        ]);
        $this->assertNotNull($result);

        $productRepo = new ProductTestRepository();
        $result = $productRepo->updateById($result->get('id'), [
            'name' => "IPhone 14 Pro",
            'type' => "Space Gray",
            'qty' => 12,
        ]);
        $this->assertNotNull($result);
    }

    /**
     * Test get product
     *
     * @group pdo-get
     * @return void
     */
    public function testGetProduct()
    {
        $productModel = new ProductTestModel();
        $result = $productModel->setAutoLoadRelations(true)->findTrashed(1, [], [], ['name', 'type']);
        $this->assertNotNull($result);
        $this->assertNotNull($result->get('category'));

        $catModel = new CategoryTestModel();
        $result = $catModel->setAutoLoadRelations(true)->findTrashed(1);
        $this->assertNotNull($result);
        $this->assertNotNull($result->get('products'));
    }

    /**
     * Test get product - repo
     *
     * @group pdo-get
     * @return void
     */
    public function testGetProductRepo()
    {
        $productModel = new ProductTestModel();
        $productRepo = new ProductTestRepository();
        $result = $productRepo->findTrashedById(1);
        $this->assertNotNull($result);
    }

    /**
     * Test get product list
     *
     * @group pdo-get
     * @return void
     */
    public function testGetProductList()
    {
        $productModel = new ProductTestModel();
        $result = $productModel->setAutoLoadRelations(true)->setPerPage(2)->all([
            'id' => [1, 2, 3],
            'AND' => [
                ['type' => ['Global', 'China']],
                'OR' => "ISNULL(updatedAt)"
            ]
        ]);
        $this->assertNotEmpty($result);
    }

    /**
     * Test get product list - repo
     *
     * @group pdo-get
     * @return void
     */
    public function testGetProductListRepo()
    {
        $productRepo = new ProductTestRepository();
        $result = $productRepo->paginate(1, 3);
        $this->assertNotEmpty($result->data);
    }
}
