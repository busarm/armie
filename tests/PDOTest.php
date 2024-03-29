<?php

namespace Armie\Test;

use Armie\App;
use Armie\Config;
use Armie\Configs\PDOConfig;
use Armie\Data\PDO\Relation;
use Armie\Dto\CollectionBaseDto;
use Armie\Tests\App\V1\Models\CategoryTestModel;
use Armie\Tests\App\V1\Models\ProductTestModel;
use Armie\Tests\App\V1\Repositories\ProductTestRepository;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @covers \Armie\Data\PDO\Connection
 * @covers \Armie\Data\PDO\Model
 * @covers \Armie\Data\PDO\Repository
 * @covers \Armie\Tests\App\V1\Models
 * @covers \Armie\Tests\App\V1\Repositories
 *
 * @group skip
 * @group pdo
 */
final class PDOTest extends TestCase
{
    private static App|null $app = null;
    private Generator|null $faker = null;

    public static function setUpBeforeClass(): void
    {
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'armie'));
        defined('APP_START_TIME') or define('APP_START_TIME', floor(microtime(true) * 1000));
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        if (!isset(self::$app)) {
            $config = (new Config())
                ->setAppPath(__DIR__ . '/app/v1')
                ->setConfigPath('Configs')
                ->setViewPath('Views')
                ->setLogRequest(false)
                ->setDb((new PDOConfig())
                    ->setConnectionDriver('mysql')
                    ->setConnectionHost('localhost')
                    ->setConnectionDatabase('default')
                    ->setConnectionPort(3306)
                    ->setConnectionUsername('root')
                    ->setConnectionPassword('root')
                    ->setConnectionPersist(false)
                    ->setConnectionErrorMode(true));
            self::$app = new App($config);
        }
        $this->faker = Factory::create();
    }

    /**
     * Test create product with model.
     *
     * @group pdo-edit
     *
     * @return void
     */
    public function testCreateProductModel()
    {
        $productModel = new ProductTestModel();
        $productModel->load([
            'name' => 'IPhone 13',
            'type' => 'Global',
            'qty'  => 5,
        ]);
        $result = $productModel->save();
        $this->assertNotNull($result);
        $this->assertNotEquals(false, $result);
        $this->assertNotNull($productModel->get('id'));
    }

    /**
     * Test create product with model.
     *
     * @group pdo-edit
     *
     * @return void
     */
    public function testUpdateProductModel()
    {
        $productModel = new ProductTestModel();
        $productModel->load([
            'name' => 'IPhone 14',
            'type' => 'Global',
            'qty'  => 10,
        ]);
        $result = $productModel->save();
        $this->assertNotNull($result);
        $this->assertNotEquals(false, $result);
        $this->assertNotNull($productModel->get('id'));

        if ($productModel->get('id')) {
            $productModel->load([
                'type' => 'China',
                'qty'  => 15,
            ]);
            $result = $productModel->save();
            $this->assertNotNull($result);
            $this->assertNotEquals(false, $result);
        }
    }

    /**
     * Test create product with repo.
     *
     * @group pdo-edit
     *
     * @return void
     */
    public function testCreateProductRepo()
    {
        $productRepo = new ProductTestRepository();
        $result = $productRepo->create([
            'name'     => 'IPhone 14',
            'type'     => 'Space Gray',
            'qty'      => 10,
            'category' => [
                'name' => $this->faker->word(),
                'desc' => $this->faker->sentence(),
            ],
            'tags' => [
                ['name' => $this->faker->word()],
                ['name' => $this->faker->word()],
                ['name' => $this->faker->word()],
                ['name' => $this->faker->word()],
                ['name' => $this->faker->word()],
                ['name' => $this->faker->word()],
                ['name' => $this->faker->word()],
                ['name' => $this->faker->word()],
            ],

        ]);
        $this->assertNotNull($result);
    }

    /**
     * Test create product with repo.
     *
     * @group pdo-edit
     *
     * @return void
     */
    public function testUpdateProductRepo()
    {
        $productRepo = new ProductTestRepository();
        $result = $productRepo->create([
            'name' => 'IPhone 14',
            'type' => 'Space Gray',
            'qty'  => 10,
        ]);
        $this->assertNotNull($result);

        $productRepo = new ProductTestRepository();
        $result = $productRepo->updateById($result->get('id'), [
            'name' => 'IPhone 14 Pro',
            'type' => 'Space Gray',
            'qty'  => 12,
        ]);
        $this->assertNotNull($result);
    }

    /**
     * Test get product.
     *
     * @group pdo-get
     *
     * @return void
     */
    public function testGetProduct()
    {
        $productModel = new ProductTestModel();
        $result = $productModel
            ->setAutoLoadRelations(false)
            ->setRequestedRelations([
                'category' => function (Relation $relation) {
                    $relation->setColumns([
                        'id', 'name',
                    ]);
                },
                'tags' => function (Relation $relation) {
                    $relation->setColumns([
                        'id', 'name',
                    ])
                        ->setLimit(2);
                },
            ])
            ->findTrashed(1);
        $this->assertNotNull($result);
        $this->assertNotNull($result->get('category'));
        $this->assertNotEmpty($result->get('tags'));

        $catModel = new CategoryTestModel();
        $result = $catModel->setAutoLoadRelations(true)->findTrashed(1);
        $this->assertNotNull($result);
        $this->assertNotEmpty($result->get('products'));
    }

    /**
     * Test get product - repo.
     *
     * @group pdo-get
     *
     * @return void
     */
    public function testGetProductRepo()
    {
        $productRepo = new ProductTestRepository();
        $result = $productRepo->findTrashedById(1);
        $this->assertNotNull($result);
    }

    /**
     * Test get product list.
     *
     * @group pdo-get
     *
     * @return void
     */
    public function testGetProductList()
    {
        $productModel = new ProductTestModel();
        $result = $productModel->setAutoLoadRelations(true)->setPerPage(2)->all([
            'id'  => [1, 2, 3],
            'AND' => [
                ['type' => ['Global', 'China']],
                'OR' => 'ISNULL(updatedAt)',
            ],
        ]);
        $collection = CollectionBaseDto::of($result, ProductTestModel::class);
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(ProductTestModel::class, $collection->at(0));
    }

    /**
     * Test get product list - repo.
     *
     * @group pdo-get
     *
     * @return void
     */
    public function testGetProductListRepo()
    {
        $productRepo = new ProductTestRepository();
        $result = $productRepo->paginate();
        $this->assertNotEmpty($result->data);
    }
}
