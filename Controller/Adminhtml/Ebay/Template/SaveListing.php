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

        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Ebay\Listing\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_SnapshotBuilder');
        $snapshotBuilder->setModel($listing);

        $oldData = $snapshotBuilder->getSnapshot();
        // ---------------------------------------
        $data = $this->getPostedTemplatesData();
        $listing->getChildObject()->addData($data);
        $listing->save();
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Ebay\Listing\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_SnapshotBuilder');
        $snapshotBuilder->setModel($listing);

        $newData = $snapshotBuilder->getSnapshot();

        $templateManager = $this->templateManager;

        $newTemplates = $templateManager->getTemplatesFromData($newData);
        $oldTemplates = $templateManager->getTemplatesFromData($oldData);

        /** @var \Ess\M2ePro\Model\Ebay\Listing\AffectedListingsProducts $affectedListingsProducts */
        $affectedListingsProducts = $this->modelFactory->getObject('Ebay_Listing_AffectedListingsProducts');
        $affectedListingsProducts->setModel($listing);

        foreach ($templateManager->getAllTemplates() as $template) {
            $templateManager->setTemplate($template);

            /** @var \Ess\M2ePro\Model\Template\SnapshotBuilder\AbstractModel $snapshotBuilder */
            if ($templateManager->isHorizontalTemplate()) {
                $snapshotBuilder = $this->modelFactory->getObject(
                    'Ebay_'.$templateManager->getTemplateModelName().'_SnapshotBuilder'
                );
            } else {
                $snapshotBuilder = $this->modelFactory->getObject(
                    $templateManager->getTemplateModelName().'_SnapshotBuilder'
                );
            }

            $snapshotBuilder->setModel($newTemplates[$template]);

            $newTemplateData = $snapshotBuilder->getSnapshot();

            /** @var \Ess\M2ePro\Model\Template\SnapshotBuilder\AbstractModel $snapshotBuilder */
            if ($templateManager->isHorizontalTemplate()) {
                $snapshotBuilder = $this->modelFactory->getObject(
                    'Ebay_'.$templateManager->getTemplateModelName().'_SnapshotBuilder'
                );
            } else {
                $snapshotBuilder = $this->modelFactory->getObject(
                    $templateManager->getTemplateModelName().'_SnapshotBuilder'
                );
            }

            $snapshotBuilder->setModel($oldTemplates[$template]);

            $oldTemplateData = $snapshotBuilder->getSnapshot();

            /** @var \Ess\M2ePro\Model\Template\Diff\AbstractModel $diff */
            if ($templateManager->isHorizontalTemplate()) {
                $diff = $this->modelFactory->getObject('Ebay_'.$templateManager->getTemplateModelName().'_Diff');
            } else {
                $diff = $this->modelFactory->getObject($templateManager->getTemplateModelName().'_Diff');
            }

            $diff->setNewSnapshot($newTemplateData);
            $diff->setOldSnapshot($oldTemplateData);

            /** @var \Ess\M2ePro\Model\Template\ChangeProcessor\AbstractModel $changeProcessor */
            if ($templateManager->isHorizontalTemplate()) {
                $changeProcessor = $this->modelFactory->getObject(
                    'Ebay_'.$templateManager->getTemplateModelName().'_ChangeProcessor'
                );
            } else {
                $changeProcessor = $this->modelFactory->getObject(
                    $templateManager->getTemplateModelName().'_ChangeProcessor'
                );
            }

            $changeProcessor->process(
                $diff,
                $affectedListingsProducts->getObjectsData(['id', 'status'], ['template' => $template])
            );
        }

        // ---------------------------------------

        $this->messageManager->addSuccess($this->__('The Listing was successfully saved.'));

        $extendedParams = [
            '*/ebay_template/editListing' => [
                'id' => $id,
                'tab' => $this->getRequest()->getPost('tab')
            ]
        ];

        return $this->_redirect($this->getHelper('Data')->getBackUrl('list', [], $extendedParams));
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
