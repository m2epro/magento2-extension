<?php

namespace Ess\M2ePro\Setup\Update\y23_m12;

class UpdateProductTypeTitleColumn extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $templateProductType = $this->getFullTableName('amazon_template_product_type');
        $dictionaryProductType = $this->getFullTableName('amazon_dictionary_product_type');

        $this->getConnection()->exec(<<<SQL
UPDATE {$templateProductType} tpt
  JOIN {$dictionaryProductType} dpt
    ON tpt.dictionary_product_type_id = dpt.id
  SET tpt.title = dpt.title
  WHERE tpt.title IS NULL;
SQL
        );
    }
}
