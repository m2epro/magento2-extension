<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Delete
 */
class Delete extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    //########################################

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->getMessageManager()->addError($this->__('Please select Item(s) to remove.'));
            $this->_redirect('*/*/index');
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel $collection */
        $collection = $this->activeRecordFactory->getObject('Ebay_Template_Category')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $ids]);

        $deleted = $locked = 0;
        foreach ($collection->getItems() as $template) {
            if ($template->isLocked()) {
                $locked++;
                continue;
            }

            $template->delete();
            $deleted++;
        }

        $tempString = $this->__('%s% record(s) were deleted.', $deleted);
        $deleted && $this->getMessageManager()->addSuccess($tempString);

        $tempString  = $this->__(
            '[%count%] Category cannot be removed until it’s unassigned from the existing products.
            Read the <a href="%url%" target="_blank">article</a> for more information.',
            $locked,
            $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/S4R8AQ')
        );
        $locked && $this->getMessageManager()->addError($tempString);

        $this->_redirect('*/*/index');
    }

    //########################################
}
