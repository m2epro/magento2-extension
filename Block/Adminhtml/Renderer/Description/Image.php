<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Renderer\Description;

class Image extends \Ess\M2ePro\Block\Adminhtml\Renderer\Description
{
    private $imageId;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('rendererDescriptionImage');
        // ---------------------------------------

        $this->setTemplate('Ess_M2ePro::renderer/description/image.phtml');
    }

    //########################################

    public function getImageId()
    {
        if ($this->imageId === null) {
            $this->imageId = substr(sha1(
                'image-'
                . $this->getData('index_number')
                . $this->dataHelper->jsonEncode($this->getData('src'))
            ), 20);
        }
        return $this->imageId;
    }

    //########################################

    public function isModeDefault()
    {
        return $this->getData('linked_mode') == \Ess\M2ePro\Helper\Module\Renderer\Description::IMAGES_MODE_DEFAULT;
    }

    //########################################
}
