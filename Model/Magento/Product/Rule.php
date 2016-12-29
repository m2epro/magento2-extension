<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

class Rule extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    protected $_form;
    protected $productFactory;
    protected $resourceIterator;

    protected $_conditions = null;

    protected $_productIds = array();

    protected $_collectedAttributes = array();

    //########################################

    public function __construct(
        \Magento\Framework\Data\Form $form,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Model\ResourceModel\Iterator $resourceIterator,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
        $this->_form = $form;
        $this->productFactory = $productFactory;
        $this->resourceIterator = $resourceIterator;
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    /**
     * Create rule instance from serialized array
     *
     * @param string $serialized
     * @throws \Ess\M2ePro\Model\Exception
     *
     */
    public function loadFromSerialized($serialized)
    {
        $prefix = $this->getPrefix();
        if (is_null($prefix)) {
            throw new \Ess\M2ePro\Model\Exception('Prefix must be specified before.');
        }

        $this->_conditions = $this->getConditionInstance($prefix);

        if (empty($serialized)) {
            return;
        }

        $conditions = unserialize($serialized);
        $this->_conditions->loadArray($conditions, $prefix);
    }

    /**
     * Create rule instance form post array
     *
     * @param array $post
     * @throws \Ess\M2ePro\Model\Exception
     *
     */
    public function loadFromPost(array $post)
    {
        $prefix = $this->getPrefix();
        if (is_null($prefix)) {
            throw new \Ess\M2ePro\Model\Exception('Prefix must be specified before.');
        }

        $this->loadFromSerialized($this->getSerializedFromPost($post, $prefix));
    }

    //########################################

    /**
     * Get serialized array from post array
     *
     * @param array $post
     * @return string
     * @throws \Ess\M2ePro\Model\Exception
     *
     */
    public function getSerializedFromPost(array $post)
    {
        $prefix = $this->getPrefix();
        if (is_null($prefix)) {
            throw new \Ess\M2ePro\Model\Exception('Prefix must be specified before.');
        }

        $conditionsArray = $this->_convertFlatToRecursive($post['rule'][$prefix], $prefix);

        return serialize($conditionsArray[$prefix][1]);
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getPrefix()
    {
        return $this->getData('prefix');
    }

    public function getStoreId()
    {
        if (is_null($this->getData('store_id'))) {
            return 0;
        }

        return $this->getData('store_id');
    }

    public function getConditionsSerialized()
    {
        return $this->getData('conditions_serialized');
    }

    public function getAttributeSets()
    {
        return $this->getData('attribute_sets');
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getCollectedAttributes()
    {
        return $this->_collectedAttributes;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setCollectedAttributes(array $attributes)
    {
        $this->_collectedAttributes = $attributes;
        return $this;
    }

    // ---------------------------------------

    public function getCustomOptionsFlag()
    {
        return $this->getData('use_custom_options');
    }

    // ---------------------------------------

    public function getForm()
    {
        return $this->_form;
    }

    // ---------------------------------------

    /**
     * Get condition instance
     *
     * @return \Ess\M2ePro\Model\Magento\Product\Rule\Condition\Combine
     * @throws \Ess\M2ePro\Model\Exception
     *
     */
    public function getConditions()
    {
        $prefix = $this->getPrefix();
        if (is_null($prefix)) {
            throw new \Ess\M2ePro\Model\Exception('Prefix must be specified before.');
        }

        if (!is_null($this->_conditions)) {
            return $this->_conditions->setJsFormObject($prefix)->setStoreId($this->getStoreId());
        }

        if (!is_null($this->getConditionsSerialized())) {
            $this->loadFromSerialized($this->getConditionsSerialized());
        } else {
            $this->_conditions = $this->getConditionInstance($prefix);
        }

        return $this->_conditions->setJsFormObject($prefix)->setStoreId($this->getStoreId());
    }

    //########################################

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if (is_null($this->_conditions)) {
            return true;
        }

        $conditionProductsCount = 0;
        foreach ($this->_conditions->getConditionModels() as $model) {
            if ($model instanceof \Ess\M2ePro\Model\Magento\Product\Rule\Condition\Product) {
                ++$conditionProductsCount;
            }
        }

        return $conditionProductsCount == 0;
    }

    /**
     * Validate magento product with rule
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return bool
     */
    public function validate(\Magento\Framework\DataObject $object)
    {
        return $this->getConditions()->validate($object);
    }

    /**
     * Add filters to magento product collection
     *
     * @param \Magento\Framework\Data\Collection\AbstractDb
     */
    public function setAttributesFilterToCollection(\Magento\Framework\Data\Collection\AbstractDb $collection)
    {
        if (count($this->getConditions()->getData($this->getPrefix())) <= 0) {
            return;
        }

        $this->_productIds = [];
        $this->getConditions()->collectValidatedAttributes($collection);

        $idFieldName = $collection->getIdFieldName();
        if (empty($idFieldName)) {
            $idFieldName = $this->productFactory->create()->getIdFieldName();
        }

        $this->resourceIterator->walk(
            $collection->getSelect(),
            [[$this, 'callbackValidateProduct']],
            [
                'attributes' => $this->getCollectedAttributes(),
                'product' => $this->productFactory->create(),
                'store_id' => $collection->getStoreId(),
                'id_field_name' => $idFieldName
            ]
        );

        $collection->addFieldToFilter($idFieldName, ['in' => $this->_productIds]);
    }

    //########################################

    public function callbackValidateProduct($args)
    {
        $product = clone $args['product'];
        $args['row']['store_id'] = $args['store_id'];
        $product->setData($args['row']);

        if ($this->validate($product)) {
            $this->_productIds[] = $product->getData($args['id_field_name']);
        }
    }

    /**
     * @return string
     */
    public function getConditionClassName()
    {
        return 'Magento\Product\Rule\Condition\Combine';
    }

    protected function getConditionInstance($prefix)
    {
        $conditionInstance = $this->modelFactory->getObject($this->getConditionClassName())
            ->setRule($this)
            ->setPrefix($prefix)
            ->setValue(true)
            ->setId(1)
            ->setData($prefix, []);

        if (!is_null($this->getCustomOptionsFlag())) {
            $conditionInstance->setCustomOptionsFlag($this->getCustomOptionsFlag());
        }

        return $conditionInstance;
    }

    protected function _convertFlatToRecursive(array $data, $prefix)
    {
        $arr = [];
        foreach ($data as $id=>$value) {
            $path = explode('--', $id);
            $node =& $arr;
            for ($i=0, $l=sizeof($path); $i<$l; $i++) {
                if (!isset($node[$prefix][$path[$i]])) {
                    $node[$prefix][$path[$i]] = [];
                }
                $node =& $node[$prefix][$path[$i]];
            }
            foreach ($value as $k => $v) {
                $node[$k] = $v;
            }
        }

        return $arr;
    }

    //########################################

    protected function _beforeSave()
    {
        $serialized = serialize($this->getConditions()->asArray());
        $this->setData('conditions_serialized', $serialized);

        return parent::_beforeSave();
    }

    //########################################

    /**
     * Using model from controller
     *
     *      get serialized data for saving to database ($serializedData):
     *          $ruleModel = $this->modelFactory->getObject('Magento\Product\Rule')->setPrefix('your_prefix')
     * ->setStoreId(0);
     *          $serializedData = $ruleModel->getSerializedFromPost($post);
     *
     *      set model to block for view rules from database ($serializedData):
     *          $ruleModel = $this->modelFactory->getObject('Magento\Product\Rule')->setPrefix('your_prefix')
     * ->setStoreId(0);
     *          $ruleModel->loadFromSerialized($serializedData);
     *
     *          $ruleBlock = $this->getLayout()
     *                            ->createBlock('M2ePro/adminhtml_magento_product_rule')
     *                            ->setData('rule_model', $ruleModel);
     *
     * Using model for check magento product with rule
     *
     *      using serialized data:
     *          $ruleModel = $this->modelFactory->getObject('Magento\Product\Rule')->setPrefix('your_prefix')
     * ->setStoreId(0);
     *          $ruleModel->loadFromSerialized($serializedData);
     *          $checkingResult = $ruleModel->validate($magentoProductInstance);
     *
     *      using post array data:
     *          $ruleModel = $this->modelFactory->getObject('Magento\Product\Rule')->setPrefix('your_prefix')
     * ->setStoreId(0);
     *          $ruleModel->loadFromPost($post);
     *          $checkingResult = $ruleModel->validate($magentoProductInstance);
     *
     * Using model for filter magento product collection with rule
     *
     *      using serialized data:
     *          $ruleModel = $this->modelFactory->getObject('Magento\Product\Rule')->setPrefix('your_prefix')
     * ->setStoreId(0);
     *          $ruleModel->loadFromSerialized($serializedData);
     *          $ruleModel->setAttributesFilterToCollection($magentoProductCollection);
     *
     *      using post array data:
     *          $ruleModel = $this->modelFactory->getObject('Magento\Product\Rule')->setPrefix('your_prefix')
     * ->setStoreId(0);
     *          $ruleModel->loadFromPost($post);
     *          $ruleModel->setAttributesFilterToCollection($magentoProductCollection);
     *
     */
}