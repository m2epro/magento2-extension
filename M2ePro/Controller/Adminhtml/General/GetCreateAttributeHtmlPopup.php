<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\GetCreateAttributeHtmlPopup
 */
class GetCreateAttributeHtmlPopup extends General
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        /** @var \Ess\M2ePro\Block\Adminhtml\General\CreateAttribute $block */
        $block = $this->createBlock('General\CreateAttribute');
        $block->setData('handler_id', $post['handler_id']);

        if (isset($post['allowed_attribute_types'])) {
            $block->setData('allowed_types', explode(',', $post['allowed_attribute_types']));
        }

        if (isset($post['apply_to_all_attribute_sets']) && !$post['apply_to_all_attribute_sets']) {
            $block->setData('apply_to_all', false);
        }

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    //########################################
}
