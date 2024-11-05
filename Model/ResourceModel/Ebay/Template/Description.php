<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template;

class Description extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_USE_SUPERSIZE_IMAGES = 'use_supersize_images';
    public const COLUMN_VIDEO_MODE = 'video_mode';
    public const COLUMN_VIDEO_ATTRIBUTE = 'video_attribute';
    public const COLUMN_COMPLIANCE_DOCUMENTS = 'compliance_documents';

    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init('m2epro_ebay_template_description', 'template_description_id');
        $this->_isPkAutoIncrement = false;
    }
}
