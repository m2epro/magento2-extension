<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\SaveListing
 */
class SaveListing extends Template
{

    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_redirect('*/ebay_listing/index');
        }

        $id = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getObjectLoaded('Listing', $id);
        ;

        // ---------------------------------------
        $oldData = $listing->getChildObject()->getDataSnapshot();
        // ---------------------------------------
        $data = $this->getPostedTemplatesData();
        $listing->getChildObject()->addData($data);
        $listing->save();
        // ---------------------------------------
        $newData = $listing->getChildObject()->getDataSnapshot();
        $listing->getChildObject()->setSynchStatusNeed($newData, $oldData);
        // ---------------------------------------

        $this->messageManager->addSuccess($this->__('The Listing was successfully saved.'));

        $extendedParams = [
            '*/ebay_template/editListing' => [
                'id' => $id,
                'tab' => $this->getRequest()->getPost('tab')
            ]
        ];

        $this->_redirect($this->getHelper('Data')->getBackUrl('list', [], $extendedParams));

        return $this->getResult();
    }

    //########################################

    private function getPostedTemplatesData()
    {
        $post = $this->getRequest()->getPost();

        // ---------------------------------------
        $data = [];
        foreach ($this->templateManager->getAllTemplates() as $nick) {
            $manager = $this->modelFactory->getObject('Ebay_Template_Manager')
                ->setTemplate($nick);

            if (!isset($post["template_{$nick}"])) {
                continue;
            }

            $templateData = $this->getHelper('Data')->jsonDecode(base64_decode($post["template_{$nick}"]));

            $templateId = $templateData['id'];
            $templateMode = $templateData['mode'];

            $idColumn = $manager->getIdColumnNameByMode($templateMode);
            $modeColumn = $manager->getModeColumnName();

            if ($idColumn !== null) {
                $data[$idColumn] = (int)$templateId;
            }

            $data[$modeColumn] = $templateMode;

            $this->clearTemplatesFieldsNotRelatedToMode($data, $nick, $templateMode);
        }
        // ---------------------------------------

        return $data;
    }

    private function clearTemplatesFieldsNotRelatedToMode(array &$data, $nick, $mode)
    {
        $modes = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_CUSTOM,
            \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE
        ];

        unset($modes[array_search($mode, $modes)]);

        foreach ($modes as $mode) {
            $column = $this->templateManager->setTemplate($nick)->getIdColumnNameByMode($mode);

            if ($column === null) {
                continue;
            }

            $data[$column] = null;
        }
    }

    //########################################
}
