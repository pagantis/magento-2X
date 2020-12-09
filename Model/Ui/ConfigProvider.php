<?php

namespace Clearpay\Clearpay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Clearpay\Clearpay\Helper\ExtraConfig;
use Magento\Framework\Locale\Resolver;

/**
 * Class ConfigProvider
 * @package Clearpay\Clearpay\Model\Ui
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'clearpay';

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

    /**
     * @var String
     */
    protected $store;

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * ConfigProvider constructor.
     *
     * @param \Magento\Payment\Helper\Data             $paymentHelper
     * @param Session                                  $checkoutSession
     * @param ExtraConfig                              $extraConfig
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param Resolver                                 $resolver
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        Session $checkoutSession,
        ExtraConfig $extraConfig,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        Resolver $resolver
    ) {
        $this->method = $paymentHelper->getMethodInstance(self::CODE);
        $this->checkoutSession = $checkoutSession;
        $this->extraConfig = $extraConfig->getExtraConfig();
        $this->assetRepository = $assetRepository;
        $this->resolver = $resolver;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        $quote = $this->checkoutSession->getQuote();
        $dividedTotal = number_format($quote->getGrandTotal()/4, 2);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $currencySymbol = $objectManager
            ->create('Magento\Directory\Model\CurrencyFactory')
            ->create()
            ->load($quote->getCurrency()->getBaseCurrencyCode())
            ->getCurrencySymbol();
        $dividedFullPrice = $dividedTotal.$currencySymbol;
        if ($currencySymbol==='GBP') {
            $dividedFullPrice = $currencySymbol.$dividedTotal;
        }

        /** @var RequestInterface $request */
        $request = $objectManager->get('Magento\Framework\App\RequestInterface');

        return [
            'payment' => [
                self::CODE => [
                    'total' => $quote->getGrandTotal(),
                    'divided_total' => $dividedTotal,
                    'enabled' => $this->method->getConfigData('active'),
                    'title' => sprintf(__($this->extraConfig['CLEARPAY_TITLE']), ' '.$dividedFullPrice),
                    'extraConfig' => serialize($this->extraConfig),
                    'title_extra' => sprintf(__($this->extraConfig['CLEARPAY_TITLE_EXTRA']), $dividedFullPrice),
                    'more_info1' => __($this->extraConfig['CLEARPAY_TITLE_MOREINFO_1']),
                    'more_info2' => __($this->extraConfig['CLEARPAY_TITLE_MOREINFO_2']),
                    'more_info3' => __($this->extraConfig['CLEARPAY_TITLE_MOREINFO_3']),
                    'image'=>'https://static.afterpay.com/integration/product-page/badge-clearpay-black-on-mint-14.png',
                    'header_image' => 'https://static-us.afterpay.com/docs/clearpay/assets/clearpay-logo-black.svg',
                    'locale' => $this->resolver->getLocale(),
                    'TCLink' => __('https://www.clearpay.co.uk/en-GB/terms-of-service'),
                    'TCText' => __('Terms and conditions')
                ]
            ],
        ];
    }

}
