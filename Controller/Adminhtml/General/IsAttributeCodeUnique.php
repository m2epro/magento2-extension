<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

class IsAttributeCodeUnique extends \Ess\M2ePro\Controller\Adminhtml\General
{
    /** @var \Magento\Eav\Model\Entity\AttributeFactory */
    private $attributeFactory;

    /** @var \Magento\Catalog\Model\ProductFactory */
    private $catalogProductFactory;

    public function __construct(
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->attributeFactory = $attributeFactory;
        $this->catalogProductFactory = $catalogProductFactory;
    }

    public function execute()
    {
        $attributeObj = $this->attributeFactory->create()->loadByCode(
            $this->catalogProductFactory->create()->getResource()->getTypeId(),
            $this->getRequest()->getParam('code')
        );

        $this->setJsonContent([
            'status' => $attributeObj->getId() === null
        ]);

        return $this->getResult();
    }
}
