<?php

namespace Pagantis\Pagantis\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ApiRegion
 * @package Pagantis\Pagantis\Model\Adminhtml\Source
 */
class ApiRegion implements ArrayInterface
{
    /**
     * EUROPE
     */
    const EUROPE = 'ES';
    /**
     * UNITEDKINGDOM
     */
    const UNITEDKINGDOM = 'GB';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => __('Europe'),
                'value' => self::EUROPE
            ),
            array(
                'label' => __('United Kingdom'),
                'value' => self::UNITEDKINGDOM
            )
        );
    }
}
