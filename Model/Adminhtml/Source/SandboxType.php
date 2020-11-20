<?php

namespace Pagantis\Pagantis\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class SandboxType
 * @package Pagantis\Pagantis\Model\Adminhtml\Source
 */
class SandboxType implements ArrayInterface
{
    /**
     * PRODUCTION
     */
    const PRODUCTION = 'production';
    /**
     * TESTING
     */
    const TESTING = 'sandbox';
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => __('Sandbox'),
                'value' => self::TESTING,
            ),
            array(
                'label' => __('Production'),
                'value' => self::PRODUCTION,
            )
        );
    }
}
