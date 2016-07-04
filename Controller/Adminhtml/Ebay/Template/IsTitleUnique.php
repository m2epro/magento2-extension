<?php
namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class IsTitleUnique extends Template
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id_value');
        $nick = $this->getRequest()->getParam('nick');
        $title = $this->getRequest()->getParam('title');

        if ($title == '') {
            $this->setJsonContent(['unique' => false]);
            return $this->getResult();
        }

        $manager = $this->templateManager->setTemplate($nick);
        $collection = $manager
            ->getTemplateModel()
            ->getCollection()
            ->addFieldToFilter('is_custom_template', 0)
            ->addFieldToFilter('title', $title);

        if ($id) {
            $collection->addFieldToFilter('id', ['neq' => $id]);
        }

        $this->setJsonContent(['unique' => !(bool)count($collection)]);
        return $this->getResult();
    }

    //########################################
}