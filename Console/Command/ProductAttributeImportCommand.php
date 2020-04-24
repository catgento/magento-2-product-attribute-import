<?php
/**
 * Product Attribute Importer Command
 * @category  Catgento
 * @package   Catgento_ProductAttributeImportCommand
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link      https://www.catgento.com
 */

namespace Catgento\ProductAttributeImport\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;

class ProductAttributeImportCommand extends Command
{
    /**
     * @var Csv
     */
    private $fileCsv;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    private $requiredHeaders = ['sku'];

    /**
     * @var array
     */
    private $headersMap;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var Product
     */
    private $productModel;

    /**
     * ProductAttributeImportCommand constructor.
     * @param Csv $fileCsv
     * @param DirectoryList $directoryList
     * @param StoreManagerInterface $storeManager
     * @param ProductRepository $productRepository
     * @param Product $productModel
     */
    public function __construct(
        Csv $fileCsv,
        DirectoryList $directoryList,
        StoreManagerInterface $storeManager,
        ProductRepository $productRepository,
        Product $productModel
    )
    {
        $this->fileCsv = $fileCsv;
        $this->directoryList = $directoryList;
        $this->storeManager = $storeManager;
        $this->storeManager->setCurrentStore('admin');
        $this->productRepository = $productRepository;
        $this->productModel = $productModel;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('import:productattribute')
            ->setDescription('Run Product Attribute importer script')
            ->setDefinition([
                new InputOption(
                    'path',
                    'p',
                    InputOption::VALUE_REQUIRED,
                    'Enter path to CSV file in Magento dir (eg. "var/import/productattributes.csv")'
                )
            ]);

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $path = $input->getOption('path');
            if (!$path) {
                throw new LocalizedException(__('Please specify path to file! (eg. "var/import/productattributes.csv")'));
            }

            $file = $this->directoryList->getRoot() . '/' . $path;

            if (!file_exists($file)) {
                throw new LocalizedException(__('File ' . $file . ' does not exist!'));
            }
            $this->fileCsv->setDelimiter(',');
            $data = $this->fileCsv->getData($file);
            $productData = array();
            $i = 0;
            foreach ($data as $row) {
                if ($i == 0) {
                    $this->mapHeaders($row);
                    foreach ($this->requiredHeaders as $requiredHeader) {
                        if (!array_key_exists($requiredHeader, $this->headersMap)) {
                            throw new LocalizedException(__('Required header "'
                                . $requiredHeader . '" is missing, please fix file'));
                        }
                    }
                    $i++;
                    continue;
                }

                if (empty($row[$this->headersMap['sku']])) {
                    continue;
                } else {
                    foreach ($this->headersMap as $key => $index) {
                        $productData[$key] = $row[$index];
                    }
                }

                $this->updateProduct($productData);
            }

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln($e->getTraceAsString());
            }
        }
    }

    /**
     * @param $productData
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function updateProduct($productData)
    {
        echo 'Importing product ' . $productData['sku'] . "\n";
        if ($this->productModel->getIdBySku($productData['sku'])) {
            $product = $this->productRepository->get($productData['sku']);

            foreach ($productData as $key => $data) {
                $product->setData($key, $data);
            }
            try {
                $this->productRepository->save($product);
            } catch (\Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Map headers from file to row keys
     *
     * @param array $row
     */
    protected function mapHeaders($row)
    {
        foreach ($row as $key => $item) {
            $this->headersMap[$item] = $key;
        }
    }
}
