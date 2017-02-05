<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

class IsAttributeCodeUnique extends General
{
    protected $attributeFactory;
    protected $catalogProductFactory;

    //########################################

    public function __construct(
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    )
    {
        $this->attributeFactory = $attributeFactory;
        $this->catalogProductFactory = $catalogProductFactory;
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        $attributeObj = $this->attributeFactory->create()->loadByCode(
            $this->catalogProductFactory->create()->getResource()->getTypeId(),
            $this->getRequest()->getParam('code')
        );

        $this->setJsonContent([
            'status' => is_null($attributeObj->getId())
        ]);

        return $this->getResult();
    }

    //########################################
}