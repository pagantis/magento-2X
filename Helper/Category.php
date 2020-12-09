<?php

namespace Clearpay\Clearpay\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Clearpay\Clearpay\Helper\Config;

/**
 * Class Category
 * @package Clearpay\Clearpay\Helper
 */
class Category extends \Magento\Catalog\Helper\Category
{
    /**
     * @var Config $config
     */
    protected $moduleConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\CollectionFactory $dataCollectionFactory,
        CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($context, $categoryFactory, $storeManager, $dataCollectionFactory, $categoryRepository);
        $this->moduleConfig = $this->getModuleConfig();
    }

    /**
     * @return mixed
     */
    private function getModuleConfig()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $config = new Config($scopeConfig);

        return $config;
    }

    /**
     * @param bool $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     * @param int  $storeId
     *
     * @return array|\Magento\Framework\Data\Tree\Node\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true, $storeId = 1)
    {
        $parent = $this->_storeManager->getStore($storeId)->getRootCategoryId();
        $cacheKey = sprintf('%d-%d-%d-%d', $parent, $sorted, $asCollection, $toLoad);
        if (isset($this->_storeCategories[$cacheKey])) {
            return $this->_storeCategories[$cacheKey];
        }

        /**
         * Check if parent node of the store still exists
         */
        $category = $this->_categoryFactory->create();
        /* @var $category ModelCategory */
        if (!$category->checkId($parent)) {
            if ($asCollection) {
                return $this->_dataCollectionFactory->create();
            }
            return [];
        }

        $recursionLevel = max(
            0,
            (int)$this->scopeConfig->getValue(
                'catalog/navigation/max_depth',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
        $storeCategories = $category->getCategories($parent, $recursionLevel, $sorted, $asCollection, $toLoad);

        $this->_storeCategories[$cacheKey] = $storeCategories;
        return $storeCategories;
    }

    /**
     * @param $itemArray
     *
     * @return array|string
     */
    public function allowedCategories($itemArray)
    {
        $excluded_categories = $this->moduleConfig->getExcludedCategories();
        $excluded_categories_array = explode(",", $excluded_categories);
        $errorResponse = array();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productModel  = $objectManager->create('Magento\Catalog\Model\Product');
        foreach ($itemArray as $item) {
            $itemId = ($item->getProductId() != null) ? $item->getProductId() : $item->getId();
            $product = $productModel->load($itemId);
            $categoriesIds = $product->getCategoryIds();
            foreach ($categoriesIds as $categoryId) {
                if (in_array($categoryId, $excluded_categories_array)) {
                    $productCategory = $objectManager
                                        ->create('Magento\Catalog\Model\Category')
                                        ->load($categoryId)
                                        ->getName();
                    $productError = sprintf(
                        "[%s] belong to a not allowed category [%s]",
                        $item->getName(),
                        $productCategory
                    );
                    array_push($errorResponse, $productError);
                }
            }
        }

        return $errorResponse;
    }
}
