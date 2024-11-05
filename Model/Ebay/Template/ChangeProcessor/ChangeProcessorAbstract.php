<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Template\ChangeProcessor;

abstract class ChangeProcessorAbstract extends \Ess\M2ePro\Model\Template\ChangeProcessorAbstract
{
    public const INSTRUCTION_TYPE_QTY_DATA_CHANGED = 'template_qty_data_changed';
    public const INSTRUCTION_TYPE_PRICE_DATA_CHANGED = 'template_price_data_changed';
    public const INSTRUCTION_TYPE_TITLE_DATA_CHANGED = 'template_title_data_changed';
    public const INSTRUCTION_TYPE_SUBTITLE_DATA_CHANGED = 'template_subtitle_data_changed';
    public const INSTRUCTION_TYPE_DESCRIPTION_DATA_CHANGED = 'template_description_data_changed';
    public const INSTRUCTION_TYPE_IMAGES_DATA_CHANGED = 'template_images_data_changed';
    public const INSTRUCTION_TYPE_VARIATION_IMAGES_DATA_CHANGED = 'template_variation_images_data_changed';
    public const INSTRUCTION_TYPE_VIDEO_DATA_CHANGED = 'template_video_data_changed';
    public const INSTRUCTION_TYPE_CATEGORIES_DATA_CHANGED = 'template_categories_data_changed';
    public const INSTRUCTION_TYPE_PARTS_DATA_CHANGED = 'template_parts_data_changed';
    public const INSTRUCTION_TYPE_SHIPPING_DATA_CHANGED = 'template_shipping_data_changed';
    public const INSTRUCTION_TYPE_RETURN_DATA_CHANGED = 'template_return_data_changed';
    public const INSTRUCTION_TYPE_OTHER_DATA_CHANGED = 'template_other_data_changed';
    public const INSTRUCTION_TYPE_COMPLIANCE_DOCUMENTS_DATA_CHANGED = 'template_compliance_documents_data_changed';
}
