<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Description;

class Specific extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const DICTIONARY_TYPE_TEXT      = 1;
    const DICTIONARY_TYPE_SELECT    = 2;
    const DICTIONARY_TYPE_CONTAINER = 3;

    const DICTIONARY_MODE_RECOMMENDED_VALUE = 'recommended_value';
    const DICTIONARY_MODE_CUSTOM_VALUE      = 'custom_value';
    const DICTIONARY_MODE_CUSTOM_ATTRIBUTE  = 'custom_attribute';
    const DICTIONARY_MODE_NONE              = 'none';

    const TYPE_INT      = 'int';
    const TYPE_FLOAT    = 'float';
    const TYPE_DATETIME = 'date_time';

    /**
     * @var \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\Description\Specific\Source[]
     */
    private $descriptionSpecificSourceModels = array();

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->amazonFactory = $amazonFactory;
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

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\Description\Specific');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->descriptionTemplateModel = NULL;
        $temp && $this->descriptionSpecificSourceModels = array();
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     * @throws \Exception
     */
    public function getDescriptionTemplate()
    {
        if (is_null($this->descriptionTemplateModel)) {

            $this->descriptionTemplateModel = $this->amazonFactory->getCachedObjectLoaded(
                'Template\Description', $this->getTemplateDescriptionId(), NULL, array('template')
            );
        }

        return $this->descriptionTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Description $instance
     */
    public function setDescriptionTemplate(\Ess\M2ePro\Model\Template\Description $instance)
    {
        $this->descriptionTemplateModel = $instance;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description
     * @throws \Exception
     */
    public function getAmazonDescriptionTemplate()
    {
        $this->getDescriptionTemplate()->getChildObject();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Amazon\Template\Description\Specific\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->descriptionSpecificSourceModels[$productId])) {
            return $this->descriptionSpecificSourceModels[$productId];
        }

        $this->descriptionSpecificSourceModels[$productId] = $this->modelFactory->getObject(
            'Amazon\Template\Description\Specific\Source'
        );
        $this->descriptionSpecificSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->descriptionSpecificSourceModels[$productId]->setDescriptionSpecificTemplate($this);

        return $this->descriptionSpecificSourceModels[$productId];
    }

    //########################################

    /**
     * @return int
     */
    public function getTemplateDescriptionId()
    {
        return (int)$this->getData('template_description_id');
    }

    /**
     * @return string
     */
    public function getXpath()
    {
        return trim($this->getData('xpath'), '/');
    }

    public function getMode()
    {
        return $this->getData('mode');
    }

    public function getIsRequired()
    {
        return $this->getData('is_required');
    }

    public function getRecommendedValue()
    {
        return $this->getData('recommended_value');
    }

    public function getCustomValue()
    {
        return $this->getData('custom_value');
    }

    public function getCustomAttribute()
    {
        return $this->getData('custom_attribute');
    }

    public function getType()
    {
        return $this->getData('type');
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $value = $this->getData('attributes');
        return is_string($value) ? (array)$this->getHelper('Data')->jsonDecode($value) : array();
    }

    //########################################

    public function isRequired()
    {
        return (bool)$this->getIsRequired();
    }

    //----------------------------------------

    public function isModeNone()
    {
        return $this->getMode() == self::DICTIONARY_MODE_NONE;
    }

    public function isModeCustomValue()
    {
        return $this->getMode() == self::DICTIONARY_MODE_CUSTOM_VALUE;
    }

    public function isModeCustomAttribute()
    {
        return $this->getMode() == self::DICTIONARY_MODE_CUSTOM_ATTRIBUTE;
    }

    public function isModeRecommended()
    {
        return $this->getMode() == self::DICTIONARY_MODE_RECOMMENDED_VALUE;
    }

    //----------------------------------------

    public function isTypeInt()
    {
        return $this->getType() == self::TYPE_INT;
    }

    public function isTypeFloat()
    {
        return $this->getType() == self::TYPE_FLOAT;
    }

    public function isTypeDateTime()
    {
        return $this->getType() == self::TYPE_DATETIME;
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return $this->getUsedAttributes();
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        $attribute = $this->getCustomAttribute();

        if (empty($attribute)) {
            return array();
        }

        return array($attribute);
    }

    //########################################
}