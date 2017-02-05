<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\InterfaceTab;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setJsonContent(['success' => false]);
            return $this->getResult();
        }

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/view/','show_products_thumbnails',
            (int)$post['products_show_thumbnails']
        );
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/view/', 'show_block_notices',
            (int)$post['block_notices_show']
        );

        $this->setJsonContent(
            ['success' => true, 'block_notices_show' => (bool)$post['block_notices_show']]
        );
        return $this->getResult();
    }

    //########################################
}