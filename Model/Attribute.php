<?php
/**
 * Barbanet_SampleAttributeInstaller
 *
 * @copyright Copyright © Magento, Inc. All rights reserved.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Barbanet\SampleAttributeInstaller\Model;

use Exception;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Framework\File\Csv;
use Magento\Framework\Model\Exception as MagentoException;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Attribute
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Attribute
{
    /**
     * @var FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var SetFactory
     */
    protected $attributeSetFactory;

    /**
     * @var CollectionFactory
     */
    protected $attrOptionCollectionFactory;

    /**
     * @var Product
     */
    protected $productHelper;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var Csv
     */
    protected $csvReader;

    /**
     * @var int
     */
    protected $entityTypeId;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param SampleDataContext $sampleDataContext
     * @param AttributeFactory $attributeFactory
     * @param SetFactory $attributeSetFactory
     * @param CollectionFactory $attrOptionCollectionFactory
     * @param Product $productHelper
     * @param Config $eavConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        AttributeFactory $attributeFactory,
        SetFactory $attributeSetFactory,
        CollectionFactory $attrOptionCollectionFactory,
        Product $productHelper,
        Config $eavConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->attributeFactory = $attributeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attrOptionCollectionFactory = $attrOptionCollectionFactory;
        $this->productHelper = $productHelper;
        $this->eavConfig = $eavConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $fixtures
     * @throws Exception
     */
    public function install(array $fixtures)
    {
        $attributeCount = 0;
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = trim($value);
                }
                $data['attribute_set'] = explode("\n", $data['attribute_set']);

                /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
                $attribute = $this->eavConfig->getAttribute('catalog_product', $data['attribute_code']);
                if (!$attribute) {
                    $attribute = $this->attributeFactory->create();
                }

                $frontendLabel = explode("\n", $data['frontend_label']);
                if (count($frontendLabel) > 1) {
                    $data['frontend_label'] = [];
                    $data['frontend_label'][\Magento\Store\Model\Store::DEFAULT_STORE_ID] = $frontendLabel[0];
                    $data['frontend_label'][$this->storeManager->getDefaultStoreView()->getStoreId()] =
                        $frontendLabel[1];
                }
                $data['option'] = $this->getOption($attribute, $data);
                $data['source_model'] = $this->productHelper->getAttributeSourceModelByInputType(
                    $data['frontend_input']
                );
                $data['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType(
                    $data['frontend_input']
                );
                $data += ['is_filterable' => 0, 'is_filterable_in_search' => 0];
                $data['backend_type'] = $attribute->getBackendTypeByInput($data['frontend_input']);

                $attribute->addData($data);
                $attribute->setIsUserDefined(1);

                $attribute->setEntityTypeId($this->getEntityTypeId());
                $attribute->save();
                $attributeId = $attribute->getId();

                if (is_array($data['attribute_set'])) {
                    foreach ($data['attribute_set'] as $setName) {
                        $setName = trim($setName);
                        $attributeCount++;
                        $attributeSet = $this->processAttributeSet($setName);
                        $attributeGroupId = $attributeSet->getDefaultGroupId();

                        $attribute = $this->attributeFactory->create()->load($attributeId);
                        $attribute
                            ->setAttributeGroupId($attributeGroupId)
                            ->setAttributeSetId($attributeSet->getId())
                            ->setEntityTypeId($this->getEntityTypeId())
                            ->setSortOrder($attributeCount + 999)
                            ->save();
                    }
                }
            }
        }
        $this->eavConfig->clear();
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param array $data
     * @return array
     */
    protected function getOption($attribute, $data)
    {
        $result = [];
        $data['option'] = explode("\n", $data['option']);
        /** @var Collection $options */
        $options = $this->attrOptionCollectionFactory->create()
            ->setAttributeFilter($attribute->getId())
            ->setPositionOrder('asc', true)
            ->load();
        foreach ($data['option'] as $value) {
            if (!$options->getItemByColumnValue('value', $value)) {
                $result[] = trim($value);
            }
        }
        return $result ? $this->convertOption($result) : $result;
    }

    /**
     * Converting attribute options from csv to correct sql values
     *
     * @param array $values
     * @return array
     */
    protected function convertOption($values)
    {
        $result = ['order' => [], 'value' => []];
        $i = 0;
        foreach ($values as $value) {
            $result['order']['option_' . $i] = (string)$i;
            $result['value']['option_' . $i] = [0 => $value, 1 => ''];
            $i++;
        }
        return $result;
    }

    /**
     * @return int
     * @throws MagentoException
     */
    protected function getEntityTypeId()
    {
        if (!$this->entityTypeId) {
            $this->entityTypeId = $this->eavConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY)->getId();
        }
        return $this->entityTypeId;
    }

    /**
     * Loads attribute set by name if attribute with such name exists
     * Otherwise creates the attribute set with $setName name and return it
     *
     * @param string $setName
     * @return Set
     * @throws Exception
     * @throws MagentoException
     */
    protected function processAttributeSet($setName)
    {
        /** @var Set $attributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $setCollection = $attributeSet->getResourceCollection()
            ->addFieldToFilter('entity_type_id', $this->getEntityTypeId())
            ->addFieldToFilter('attribute_set_name', $setName)
            ->load();
        $attributeSet = $setCollection->fetchItem();

        if (!$attributeSet) {
            $attributeSet = $this->attributeSetFactory->create();
            $attributeSet->setEntityTypeId($this->getEntityTypeId());
            $attributeSet->setAttributeSetName($setName);
            $attributeSet->save();
            $defaultSetId = $this->eavConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY)
                ->getDefaultAttributeSetId();
            $attributeSet->initFromSkeleton($defaultSetId);
            $attributeSet->save();
        }
        return $attributeSet;
    }
}
