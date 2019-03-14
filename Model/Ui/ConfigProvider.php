<?php

namespace DigitalOrigin\Pmt\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use DigitalOrigin\Pmt\Helper\ExtraConfig;

/**
 * Class ConfigProvider
 * @package DigitalOrigin\Pmt\Model\Ui
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'pagantis';

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $method;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var String
     */
    protected $extraConfig;

    /**
     * @var String
     */
    protected $assetRepository;


    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        Session $checkoutSession,
        ExtraConfig $extraConfig,
        \Magento\Framework\View\Asset\Repository $assetRepository
    ) {
        $this->method = $paymentHelper->getMethodInstance(self::CODE);
        $this->checkoutSession = $checkoutSession;
        $this->extraConfig = $extraConfig->getExtraConfig();
        $this->assetRepository = $assetRepository;
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
                    'displayMode' => $this->method->getConfigData('display_mode'),
                    'title' => __($this->extraConfig['PAGANTIS_TITLE']),
                    'subtitle' => __($this->extraConfig['PAGANTIS_TITLE_EXTRA']),
                    'image' => $this->assetRepository->getUrl('DigitalOrigin_Pmt::logopagantis.png')
                ],
            ],
        ];
    }
}
