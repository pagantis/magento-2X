<?php

namespace DigitalOrigin\Pmt\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class SimulatorType
 * @package DigitalOrigin\Pmt\Model\Adminhtml\Source
 */
class SimulatorType implements ArrayInterface
{
    /**
     * NO
     */
    const NO = 0;

    /**
     * MINI
     */
    const MINI = 6;

    /**
     * COMPLETE
     */
    const COMPLETE = 2;

    /**
     * SELECTOR
     */
    const SELECTOR = 3;

    /**
     * TEXT
     */
    const TEXT = 4;

    /**
     * TEXT
     */
    const SIMPLE = 1;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => __(' Mini'),
                'value' => self::MINI,
            ),
            array(
                'label' => __(' Complete'),
                'value' => self::COMPLETE,
            ),
            array(
                'label' => __(' Selector'),
                'value' => self::SELECTOR,
            ),
            array(
                'label' => __(' Descriptive Text'),
                'value' => self::TEXT,
            ),
            array(
                'label' => __(' Simple'),
                'value' => self::SIMPLE,
            ),
            array(
                'label' => __(' Do not show'),
                'value' => self::NO,
            )
        );
    }
}
