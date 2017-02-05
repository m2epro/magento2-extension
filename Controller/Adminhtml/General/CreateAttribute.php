<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

class CreateAttribute extends General
{
    protected $entityAttributeSetFactory;

    //########################################

    public function __construct(
        \Magento\Eav\Model\Entity\Attribute\SetFactory $entityAttributeSetFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    )
    {
        $this->entityAttributeSetFactory = $entityAttributeSetFactory;
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Magento\Attribute\Builder $model */
        $model = $this->modelFactory->getObject('Magento\Attribute\Builder');

        $model->setLabel($this->getRequest()->getParam('store_label'))
            ->setCode($this->getRequest()->getParam('code'))
            ->setInputType($this->getRequest()->getParam('input_type'))
            ->setDefaultValue($this->getRequest()->getParam('default_value'))
            ->setScope($this->getRequest()->getParam('scope'));

        $attributeResult = $model->save();

        if (!isset($attributeResult['result']) || !$attributeResult['result']) {

            $this->setJsonContent($attributeResult);
            return $this->getResult();
        }

        foreach ($this->getRequest()->getParam('attribute_sets', array()) as $seId) {

            /** @var \Magento\Eav\Model\Entity\Attribute\Set $set */
            $set = $this->entityAttributeSetFactory->create()->load($seId);

            if (!$set->getId()) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Magento\Attribute\Relation $model */
            $model = $this->modelFactory->getObject('Magento\Attribute\Relation');
            $model->setAttributeObj($attributeResult['obj'])
                ->setAttributeSetObj($set);

            $setResult = $model->save();

            if (!isset($setResult['result']) || !$setResult['result']) {

                $this->setJsonContent($setResult);
                return $this->getResult();
            }
        }

        unset($attributeResult['obj']);
        $this->setJsonContent($attributeResult);
        return $this->getResult();
    }

    //########################################
}