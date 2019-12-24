<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request
{
    protected $resourceConnection;
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    protected function searchNotFoundAttributes()
    {
        $this->getMagentoProduct()->clearNotFoundAttributes();
    }

    protected function processNotFoundAttributes($title)
    {
        $attributes = $this->getMagentoProduct()->getNotFoundAttributes();

        if (empty($attributes)) {
            return true;
        }

        $this->addNotFoundAttributesMessages($title, $attributes);

        return false;
    }

    // ---------------------------------------

    protected function addNotFoundAttributesMessages($title, array $attributes)
    {
        $attributesTitles = [];

        foreach ($attributes as $attribute) {
            $attributesTitles[] = $this->getHelper('Magento\Attribute')
                                       ->getAttributeLabel(
                                           $attribute,
                                           $this->getListing()->getStoreId()
                                       );
        }
        // M2ePro\TRANSLATIONS
        // %attribute_title%: Attribute(s) %attributes% were not found in this Product and its value was not sent.
        $this->addWarningMessage(
            $this->getHelper('Module\Translation')->__(
                '%attribute_title%: Attribute(s) %attributes% were not found'.
                ' in this Product and its value was not sent.',
                $this->getHelper('Module\Translation')->__($title),
                implode(',', $attributesTitles)
            )
        );
    }

    //########################################
}
