<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template;

class Description extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_TEMPLATE_DESCRIPTION_ID = 'template_description_id';
    public const COLUMN_USE_SUPERSIZE_IMAGES = 'use_supersize_images';
    public const COLUMN_VIDEO_MODE = 'video_mode';
    public const COLUMN_VIDEO_ATTRIBUTE = 'video_attribute';
    public const COLUMN_COMPLIANCE_DOCUMENTS = 'compliance_documents';
    public const COLUMN_CONDITION_MODE = 'condition_mode';
    public const COLUMN_CONDITION_VALUE = 'condition_value';
    public const COLUMN_CONDITION_ATTRIBUTE = 'condition_attribute';
    public const COLUMN_CONDITION_NOTE_TEMPLATE = 'condition_note_template';
    public const COLUMN_CONDITION_PROFESSIONAL_GRADER_ID = 'condition_professional_grader_id';
    public const COLUMN_CONDITION_GRADE_ID = 'condition_grade_id';
    public const COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER = 'condition_grade_certification_number';
    public const COLUMN_CONDITION_GRADE_CARD_CONDITION_ID = 'condition_grade_card_condition_id';

    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_TEMPLATE_DESCRIPTION,
            self::COLUMN_TEMPLATE_DESCRIPTION_ID
        );

        $this->_isPkAutoIncrement = false;
    }
}
