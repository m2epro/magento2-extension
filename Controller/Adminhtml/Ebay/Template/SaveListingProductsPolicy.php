<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class SaveListingProductsPolicy extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Template
{
    protected $transaction;

    //########################################

    public function __construct(
        \Magento\Framework\DB\Transaction $transaction,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    )
    {
        $this->transaction = $transaction;
        parent::__construct($templateManager, $ebayFactory, $context);
    }

    //########################################

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (!$post = $this->getRequest()->getPostValue() || empty($ids)) {
            $this->setAjaxContent('', false);
            return $this->getResult();
        }

        // ---------------------------------------
        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $ids]);
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            $this->setAjaxContent('', false);
            return $this->getResult();
        }

        // ---------------------------------------
        $data = $this->getPostedTemplatesData();
        // ---------------------------------------

        // ---------------------------------------

        $snapshots = [];

        try {
            foreach ($collection->getItems() as $listingProduct) {
                /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
                $snapshots[$listingProduct->getId()] = array_merge(
                    $listingProduct->getDataSnapshot(),
                    $listingProduct->getChildObject()->getDataSnapshot()
                );
                $listingProduct->addData($data);
                $listingProduct->getChildObject()->addData($data);
                $this->transaction->addObject($listingProduct);
            }

            $this->transaction->save();
        } catch (\Exception $e) {
            $snapshots = false;
        }
        // ---------------------------------------

        if ($snapshots) {
            foreach ($collection->getItems() as $listingProduct) {
                $listingProduct->getChildObject()->setSynchStatusNeed(
                    array_merge(
                        $listingProduct->getDataSnapshot(),
                        $listingProduct->getChildObject()->getDataSnapshot()
                    ),
                    $snapshots[$listingProduct->getId()]
                );
            }
        }

        $this->setAjaxContent('', false);
        return $this->getResult();
    }

    //########################################

    private function getPostedTemplatesData()
    {
        if (!$post = $this->getRequest()->getPostValue()) {
            return [];
        }

        // ---------------------------------------
        $data = [];
        foreach ($this->templateManager->getAllTemplates() as $nick) {
            $manager = $this->templateManager->setTemplate($nick);

            if (!isset($post["template_{$nick}"])) {
                continue;
            }

            $templateData = $this->getHelper('Data')->jsonDecode(base64_decode($post["template_{$nick}"]));

            $templateId = $templateData['id'];
            $templateMode = $templateData['mode'];

            $idColumn = $manager->getIdColumnNameByMode($templateMode);
            $modeColumn = $manager->getModeColumnName();

            if (!is_null($idColumn)) {
                $data[$idColumn] = (int)$templateId;
            }

            $data[$modeColumn] = $templateMode;

            $this->clearTemplatesFieldsNotRelatedToMode($data, $nick, $templateMode);
        }
        // ---------------------------------------

        return $data;
    }

    // ---------------------------------------

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

            if (is_null($column)) {
                continue;
            }

            $data[$column] = NULL;
        }
    }

    //########################################
}