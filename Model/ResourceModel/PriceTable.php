<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * PriceTable Resource Model
 */
class PriceTable extends AbstractDb
{
    /**
     * Table name
     */
    public const TABLE_NAME = 'pl_price_table';

    /**
     * Primary key field
     */
    public const ID_FIELD_NAME = 'id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD_NAME);
    }
}
