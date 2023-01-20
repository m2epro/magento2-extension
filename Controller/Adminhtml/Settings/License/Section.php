<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\License;

class Section extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    public function execute()
    {
        $content = $this->getLayout()
                        ->createBlock(\Ess\M2ePro\Block\Adminhtml\System\Config\Sections\License::class);
        $this->setAjaxContent($content);

        return $this->getResult();
    }
}
