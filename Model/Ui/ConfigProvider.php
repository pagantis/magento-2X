<?php

namespace DigitalOrigin\Pmt\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;

/**
 * Class ConfigProvider
 * @package DigitalOrigin\Pmt\Model\Ui
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paylater';

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * ConfigProvider constructor.
     *
     * @param Session $checkoutSession
     */
    public function __construct(Session $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $quote = $this->checkoutSession->getQuote();

        return [
            'payment' => [
                self::CODE => [
                    'total' => $quote->getGrandTotal(),
                ],
            ],
        ];
    }
}
