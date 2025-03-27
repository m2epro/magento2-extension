<?php

declare(strict_types=1);

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

    public const COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_MODE = 'condition_professional_grader_id_mode';
    public const COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_ATTRIBUTE = 'condition_professional_grader_id_attribute';
    public const COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_VALUE = 'condition_professional_grader_id_value';

    public const COLUMN_CONDITION_GRADE_ID_MODE = 'condition_grade_id_mode';
    public const COLUMN_CONDITION_GRADE_ID_ATTRIBUTE = 'condition_grade_id_attribute';
    public const COLUMN_CONDITION_GRADE_ID_VALUE = 'condition_grade_id_value';

    public const COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_MODE = 'condition_grade_certification_number_mode';
    public const COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_ATTRIBUTE = 'condition_grade_certification_attribute';
    public const COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_CUSTOM_VALUE
        = 'condition_grade_certification_number_custom_value';

    public const COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_MODE = 'condition_grade_card_condition_id_mode';
    public const COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_ATTRIBUTE = 'condition_grade_card_condition_id_attribute';
    public const COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_VALUE = 'condition_grade_card_condition_id_value';

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
