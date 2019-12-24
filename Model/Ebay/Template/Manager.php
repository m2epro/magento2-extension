<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    private $ownerObject = null;
    private $templateNick = null;
    private $resultObject = null;

    const MODE_PARENT   = 0;
    const MODE_CUSTOM   = 1;
    const MODE_TEMPLATE = 2;

    const COLUMN_PREFIX = 'template';

    const OWNER_LISTING = 'Listing';
    const OWNER_LISTING_PRODUCT = 'Listing\Product';

    const TEMPLATE_RETURN_POLICY = 'return_policy';
    const TEMPLATE_PAYMENT = 'payment';
    const TEMPLATE_SHIPPING = 'shipping';
    const TEMPLATE_DESCRIPTION = 'description';
    const TEMPLATE_SELLING_FORMAT = 'selling_format';
    const TEMPLATE_SYNCHRONIZATION = 'synchronization';

    protected $activeRecordFactory;
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing|\Ess\M2ePro\Model\Ebay\Listing\Product
     */
    public function getOwnerObject()
    {
        return $this->ownerObject;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing|\Ess\M2ePro\Model\Ebay\Listing\Product $object
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function setOwnerObject($object)
    {
        if (!($object instanceof \Ess\M2ePro\Model\Ebay\Listing) &&
            !($object instanceof \Ess\M2ePro\Model\Ebay\Listing\Product)) {
            throw new \Ess\M2ePro\Model\Exception('Owner object is out of knowledge range.');
        }
        $this->ownerObject = $object;
        return $this;
    }

    //########################################

    /**
     * @return bool
     */
    public function isListingOwner()
    {
        return $this->getOwnerObject() instanceof \Ess\M2ePro\Model\Ebay\Listing;
    }

    /**
     * @return bool
     */
    public function isListingProductOwner()
    {
        return $this->getOwnerObject() instanceof \Ess\M2ePro\Model\Ebay\Listing\Product;
    }

    //########################################

    /**
     * @return null|string
     */
    public function getTemplate()
    {
        return $this->templateNick;
    }

    /**
     * @param string $nick
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function setTemplate($nick)
    {
        if (!in_array(strtolower($nick), $this->getAllTemplates())) {
            throw new \Ess\M2ePro\Model\Exception('Policy nick is out of knowledge range.');
        }
        $this->templateNick = strtolower($nick);
        return $this;
    }

    //########################################

    /**
     * @return array
     */
    public function getAllTemplates()
    {
        return [
            self::TEMPLATE_RETURN_POLICY,
            self::TEMPLATE_SHIPPING,
            self::TEMPLATE_PAYMENT,
            self::TEMPLATE_DESCRIPTION,
            self::TEMPLATE_SELLING_FORMAT,
            self::TEMPLATE_SYNCHRONIZATION
        ];
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isFlatTemplate()
    {
        return in_array($this->getTemplate(), $this->getFlatTemplates());
    }

    /**
     * @return array
     */
    public function getFlatTemplates()
    {
        return [
            self::TEMPLATE_RETURN_POLICY,
            self::TEMPLATE_SHIPPING,
            self::TEMPLATE_PAYMENT,
        ];
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isHorizontalTemplate()
    {
        return in_array($this->getTemplate(), $this->getHorizontalTemplates());
    }

    /**
     * @return array
     */
    public function getHorizontalTemplates()
    {
        return [
            self::TEMPLATE_SELLING_FORMAT,
            self::TEMPLATE_SYNCHRONIZATION,
            self::TEMPLATE_DESCRIPTION
        ];
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMarketplaceDependentTemplate()
    {
        return in_array($this->getTemplate(), $this->getMarketplaceDependentTemplates());
    }

    /**
     * @return array
     */
    public function getMarketplaceDependentTemplates()
    {
        return [
            self::TEMPLATE_PAYMENT,
            self::TEMPLATE_SHIPPING,
            self::TEMPLATE_RETURN_POLICY,
        ];
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isTrackingAttributesTemplate()
    {
        return in_array($this->getTemplate(), $this->getTrackingAttributesTemplates());
    }

    /**
     * @return array
     */
    public function getTrackingAttributesTemplates()
    {
        return [
            self::TEMPLATE_RETURN_POLICY,
            self::TEMPLATE_SHIPPING,
            self::TEMPLATE_PAYMENT,
            self::TEMPLATE_DESCRIPTION,
            self::TEMPLATE_SELLING_FORMAT
        ];
    }

    //########################################

    /**
     * @return string
     */
    public function getModeColumnName()
    {
        return self::COLUMN_PREFIX.'_'.$this->getTemplate().'_mode';
    }

    /**
     * @return string
     */
    public function getCustomIdColumnName()
    {
        return self::COLUMN_PREFIX.'_'.$this->getTemplate().'_custom_id';
    }

    /**
     * @return string
     */
    public function getTemplateIdColumnName()
    {
        return self::COLUMN_PREFIX.'_'.$this->getTemplate().'_id';
    }

    //########################################

    /**
     * @param int $mode
     * @return null|string
     */
    public function getIdColumnNameByMode($mode)
    {
        $name = null;

        switch ($mode) {
            case self::MODE_TEMPLATE:
                $name = $this->getTemplateIdColumnName();
                break;
            case self::MODE_CUSTOM:
                $name = $this->getCustomIdColumnName();
                break;
        }

        return $name;
    }

    public function getIdColumnValue()
    {
        $idColumnName = $this->getIdColumnNameByMode($this->getModeValue());

        if ($idColumnName === null) {
            return null;
        }

        return $this->getOwnerObject()->getData($idColumnName);
    }

    //########################################

    public function getModeValue()
    {
        return $this->getOwnerObject()->getData($this->getModeColumnName());
    }

    public function getCustomIdValue()
    {
        return $this->getOwnerObject()->getData($this->getCustomIdColumnName());
    }

    public function getTemplateIdValue()
    {
        return $this->getOwnerObject()->getData($this->getTemplateIdColumnName());
    }

    //########################################

    public function getParentResultObject()
    {
        if ($this->isListingOwner()) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $manager */
        $manager = $this->modelFactory->getObject('Ebay_Template_Manager');
        $manager->setTemplate($this->getTemplate());
        $manager->setOwnerObject($this->getOwnerObject()->getEbayListing());

        return $manager->getResultObject();
    }

    public function getCustomResultObject()
    {
        $id = $this->getCustomIdValue();

        if ($id === null) {
            return null;
        }

        return $this->makeResultObject($id);
    }

    public function getTemplateResultObject()
    {
        $id = $this->getTemplateIdValue();

        if ($id === null) {
            return null;
        }

        return $this->makeResultObject($id);
    }

    // ---------------------------------------

    private function makeResultObject($id)
    {
        $modelName = $this->getTemplateModelName();

        if ($this->isHorizontalTemplate()) {
            $object = $this->ebayFactory->getCachedObjectLoaded(
                $modelName,
                $id
            );
        } else {
            $object = $this->activeRecordFactory->getCachedObjectLoaded(
                $modelName,
                $id
            );
        }

        return $object;
    }

    //########################################

    /**
     * @return bool
     */
    public function isModeParent()
    {
        return $this->getModeValue() == self::MODE_PARENT;
    }

    /**
     * @return bool
     */
    public function isModeCustom()
    {
        return $this->getModeValue() == self::MODE_CUSTOM;
    }

    /**
     * @return bool
     */
    public function isModeTemplate()
    {
        return $this->getModeValue() == self::MODE_TEMPLATE;
    }

    //########################################

    public function getResultObject()
    {
        if ($this->resultObject !== null) {
            return $this->resultObject;
        }

        if ($this->isModeParent()) {
            $this->resultObject = $this->getParentResultObject();
        }

        if ($this->isModeCustom()) {
            $this->resultObject = $this->getCustomResultObject();
        }

        if ($this->isModeTemplate()) {
            $this->resultObject = $this->getTemplateResultObject();
        }

        if ($this->resultObject === null) {
            throw new \Ess\M2ePro\Model\Exception('Unable to get result object.');
        }

        return $this->resultObject;
    }

    //########################################

    /**
     * @return null|string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getTemplateModelName()
    {
        $name = null;

        switch ($this->getTemplate()) {
            case self::TEMPLATE_PAYMENT:
                $name = 'Ebay_Template_Payment';
                break;
            case self::TEMPLATE_SHIPPING:
                $name = 'Ebay_Template_Shipping';
                break;
            case self::TEMPLATE_RETURN_POLICY:
                $name = 'Ebay_Template_ReturnPolicy';
                break;
            case self::TEMPLATE_SELLING_FORMAT:
                $name = 'Template\SellingFormat';
                break;
            case self::TEMPLATE_DESCRIPTION:
                $name = 'Template\Description';
                break;
            case self::TEMPLATE_SYNCHRONIZATION:
                $name = 'Template\Synchronization';
                break;
        }

        if ($name === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Template nick "%s" is unknown.', $this->getTemplate())
            );
        }

        return $name;
    }

    public function getTemplateModel($returnChildModel = false)
    {
        $model = null;

        switch ($this->getTemplate()) {
            case self::TEMPLATE_PAYMENT:
            case self::TEMPLATE_SHIPPING:
            case self::TEMPLATE_RETURN_POLICY:
                $model = $this->activeRecordFactory->getObject($this->getTemplateModelName());
                break;

            case self::TEMPLATE_SELLING_FORMAT:
            case self::TEMPLATE_SYNCHRONIZATION:
            case self::TEMPLATE_DESCRIPTION:
                if ($returnChildModel) {
                    $modelPath = ucfirst(\Ess\M2ePro\Helper\Component\Ebay::NICK).'\\'.$this->getTemplateModelName();
                    $model = $this->activeRecordFactory->getObject($modelPath);
                } else {
                    $model = $this->ebayFactory->getObject($this->getTemplateModelName());
                }
                break;
        }

        if ($model === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Template nick "%s" is unknown.', $this->getTemplate())
            );
        }

        return $model;
    }

    public function getTemplateCollection()
    {
        $collection = null;

        switch ($this->getTemplate()) {
            case self::TEMPLATE_PAYMENT:
            case self::TEMPLATE_SHIPPING:
            case self::TEMPLATE_RETURN_POLICY:
                $collection = $this->getTemplateModel()->getCollection();
                break;

            case self::TEMPLATE_SELLING_FORMAT:
            case self::TEMPLATE_SYNCHRONIZATION:
            case self::TEMPLATE_DESCRIPTION:
                $collection = $this->ebayFactory->getObject($this->getTemplateModelName())->getCollection();
                break;
        }

        if ($collection === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Template nick "%s" is unknown.', $this->getTemplate())
            );
        }

        return $collection;
    }

    public function getTemplateBuilder()
    {
        $model = null;

        switch ($this->getTemplate()) {
            case self::TEMPLATE_PAYMENT:
                $model = $this->modelFactory->getObject('Ebay_Template_Payment_Builder');
                break;
            case self::TEMPLATE_SHIPPING:
                $model = $this->modelFactory->getObject('Ebay_Template_Shipping_Builder');
                break;
            case self::TEMPLATE_RETURN_POLICY:
                $model = $this->modelFactory->getObject('Ebay_Template_ReturnPolicy_Builder');
                break;
            case self::TEMPLATE_SELLING_FORMAT:
                $model = $this->modelFactory->getObject('Ebay_Template_SellingFormat_Builder');
                break;
            case self::TEMPLATE_DESCRIPTION:
                $model = $this->modelFactory->getObject('Ebay_Template_Description_Builder');
                break;
            case self::TEMPLATE_SYNCHRONIZATION:
                $model = $this->modelFactory->getObject('Ebay_Template_Synchronization_Builder');
                break;
        }

        if ($model === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Template nick "%s" is unknown.', $this->getTemplate())
            );
        }

        return $model;
    }

    //########################################

    /**
     * @param string $ownerObjectModel
     * @param int $templateId
     * @param bool $asArrays
     * @param string|array $columns
     * @return array
     */
    public function getAffectedOwnerObjects($ownerObjectModel, $templateId, $asArrays = true, $columns = '*')
    {
        $collection = $this->ebayFactory->getObject($ownerObjectModel)->getCollection();

        $where = "({$this->getModeColumnName()} = " .\Ess\M2ePro\Model\Ebay\Template\Manager::MODE_CUSTOM;
        $where .= " AND {$this->getCustomIdColumnName()} = " . (int)$templateId . ")";

        $where .= ' OR ';

        $where .= "({$this->getModeColumnName()} = " .\Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE;
        $where .= " AND {$this->getTemplateIdColumnName()} = " . (int)$templateId . ")";

        $collection->getSelect()->where($where);

        if (is_array($columns) && !empty($columns)) {
            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collection->getSelect()->columns($columns);
        }

        return $asArrays ? (array)$collection->getData() : (array)$collection->getItems();
    }

    public function getTemplatesFromData($data)
    {
        $resultTemplates = [];

        foreach ($this->getAllTemplates() as $template) {
            $this->setTemplate($template);

            $templateMode = $data[$this->getModeColumnName()];

            if ($templateMode == self::MODE_PARENT) {
                $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $data['listing_id']);
                $templateMode = $listing->getChildObject()->getData($this->getModeColumnName());
                $templateId   = $listing->getChildObject()->getData($this->getIdColumnNameByMode($templateMode));
            } else {
                $templateId = $data[$this->getIdColumnNameByMode($templateMode)];
            }

            $templateModelName = $this->getTemplateModelName();

            if ($this->isHorizontalTemplate()) {
                $templateModel = $this->ebayFactory
                    ->getCachedObjectLoaded($templateModelName, $templateId)
                    ->getChildObject();
            } else {
                $templateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    $templateModelName,
                    $templateId
                );
            }

            $resultTemplates[$template] = $templateModel;
        }

        return $resultTemplates;
    }

    //########################################
}
