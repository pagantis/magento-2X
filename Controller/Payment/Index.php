<?php

namespace Clearpay\Clearpay\Controller\Payment;

use Afterpay\SDK\Model;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Checkout\Model\Session;
use Clearpay\Clearpay\Helper\Category;
use Clearpay\Clearpay\Helper\Config;
use Clearpay\Clearpay\Helper\ExtraConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Api\Data\StoreInterface;
use Afterpay\SDK\HTTP\Request\CreateCheckout;
use Afterpay\SDK\MerchantAccount;
use Clearpay\Clearpay\Logger\Logger;

/**
 * Class Index
 * @package Clearpay\Clearpay\Controller\Payment
 */
class Index extends Action
{
    /** Orders tablename */
    const ORDERS_TABLE = 'cart_process';

    /** Concurrency tablename */
    const LOGS_TABLE = 'Clearpay_logs';

    /** @var Context $context */
    protected $context;

    /** @var QuoteRepository  $quoteRepository */
    protected $quoteRepository;

    /** @var OrderCollection $orderCollection */
    protected $orderCollection;

    /** @var Session $session */
    protected $session;

    /** @var mixed $config */
    protected $config;

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /** @var ProductMetadataInterface $productMetadataInterface */
    protected $productMetadataInterface;

    /** @var ModuleList $moduleList */
    protected $moduleList;

    /** @var ExtraConfig $extraConfig */
    protected $extraConfig;
    
    /** @var StoreInterface $store */
    protected $store;

    /** @var QuoteRepository $quote */
    protected $quote;

    /** @var Category $category */
    protected $category;

    /** @var Logger $logger */
    protected $logger;

    /**
     * Index constructor.
     *
     * @param Context                  $context
     * @param QuoteRepository          $quoteRepository
     * @param OrderCollection          $orderCollection
     * @param Session                  $session
     * @param Config                   $config
     * @param ResourceConnection       $dbObject
     * @param ProductMetadataInterface $productMetadataInterface
     * @param ModuleList               $moduleList
     * @param ExtraConfig              $extraConfig
     * @param StoreInterface           $storeInterface
     * @param Category                 $catalogCategory
     * @param Logger                   $logger
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Context $context,
        QuoteRepository $quoteRepository,
        OrderCollection $orderCollection,
        Session $session,
        Config $config,
        ResourceConnection $dbObject,
        ProductMetadataInterface $productMetadataInterface,
        ModuleList $moduleList,
        ExtraConfig $extraConfig,
        StoreInterface $storeInterface,
        Category $catalogCategory,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->context = $context;
        $this->config = $config;
        $this->quoteRepository = $quoteRepository;
        $this->orderCollection = $orderCollection;
        $this->dbObject = $dbObject;
        $this->moduleList = $moduleList;
        $this->productMetadataInterface = $productMetadataInterface;
        $this->extraConfig = $extraConfig->getExtraConfig();
        $this->store = $storeInterface;
        $this->quote = $this->session->getQuote();
        $this->category = $catalogCategory;
        $this->logger = $logger;
    }

    public function execute()
    {
        $cancelUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);
        try {
            Model::setAutomaticValidationEnabled(true);
            $createCheckoutRequest   = new CreateCheckout();
            $clearpayMerchantAccount = new MerchantAccount();
            $countryCode             = $this->getCountryCode();
            $clearpayMerchantAccount
                ->setMerchantId($this->config->getMerchantId())
                ->setSecretKey($this->config->getSecretKey())
                ->setApiEnvironment($this->config->getApiEnvironment());

            if (!is_null($countryCode)) {
                $clearpayMerchantAccount->setCountryCode($countryCode);
            }

            $metadata  = $this->getMetadata();
            $uriRoute  = 'clearpay/notify/index';
            $urlToken  = strtoupper(md5(uniqid(rand(), true)));
            $token     = md5($urlToken);

            $quoteId = $this->quote->getId();
            if (version_compare($metadata['ec_version'], '2.3.0') >= 0) {
                $uriRoute = 'clearpay/notify/indexV2';
            }
            $okUrl           = $this->_url->getUrl($uriRoute, ['_query' => ['quoteId' => $quoteId, 'token' => $token]]);
            $currency        = $this->quote->getCurrency()->getBaseCurrencyCode();
            $shippingAddress = $this->quote->getShippingAddress();
            $billingAddress  = $this->quote->getBillingAddress();
            $this->logger->info('MerchantReference=');
            $customer = $this->quote->getCustomer();
            $params   = $this->getRequest()->getParams();
            $email    = '';
            if (isset($params['email']) && $params['email'] != '') { //GUEST USER
                $this->session->setEmail($params['email']); //Get guest email after refresh page
                $customer->setEmail($params['email']);
                $this->quote->setCheckoutMethod('guest');
                $this->quote->getBillingAddress()->setEmail($params['email']);
                $email = $params['email'];
            } elseif ($customer->getEmail() != '') { // REGISTER USER
                $this->quote->getBillingAddress()->setEmail($customer->getEmail());
                $email = $customer->getEmail();
            } else { // CORNER CASE?
                $customer->setEmail($this->session->getEmail());
                $this->quote->setCheckoutMethod('guest');
                $this->quote->getBillingAddress()->setEmail($this->session->getEmail());
                $email = $this->session->getEmail();
            }

            /** @var Quote $currentQuote */
            $currentQuote = $this->quoteRepository->get($this->quote->getId());
            $currentQuote->setCustomerEmail($email);
            $this->quoteRepository->save($currentQuote);

            $createCheckoutRequest
                ->setMerchant(
                    array(
                        'redirectConfirmUrl' => $okUrl,
                        'redirectCancelUrl'  => $cancelUrl
                    )
                )
                ->setMerchantAccount($clearpayMerchantAccount)
                ->setTotalAmount(
                    $this->parseAmount($this->quote->getGrandTotal()),
                    $currency
                )
                ->setTaxAmount(
                    $this->parseAmount($this->quote->getTaxAmount()),
                    $currency
                )
                ->setConsumer(
                    array(
                        'phoneNumber' => $billingAddress->getTelephone(),
                        'givenNames'  => $shippingAddress->getFirstname() ? $shippingAddress->getFirstname(
                        ) : $customer->getFirstname(),
                        'surname'     => $shippingAddress->getLastname() ? $shippingAddress->getLastname(
                        ) : $customer->getLastname(),
                        'email'       => $email
                    )
                )
                ->setBilling(
                    array(
                        'name'  => $billingAddress->getFirstname()." ".$billingAddress->getLastname(),
                        'line1' => $billingAddress->getStreetLine(1),
                        'line2' => $billingAddress->getStreetLine(2),
                        'suburb' => $billingAddress->getCity(),
                        'state' => $billingAddress->getCountry(),
                        'postcode' => $billingAddress->getPostcode(),
                        'countryCode' => $billingAddress->getCountryId(),
                        'phoneNumber' => $billingAddress->getTelephone()
                    )
                )
                ->setShipping(
                    array(
                        'name'  => $shippingAddress->getFirstname()." ".$shippingAddress->getLastname(),
                        'line1' => $shippingAddress->getStreetLine(1),
                        'line2' => $shippingAddress->getStreetLine(2),
                        'suburb' => $shippingAddress->getCity(),
                        'state' => $shippingAddress->getCountry(),
                        'postcode' => $shippingAddress->getPostcode(),
                        'countryCode' => $shippingAddress->getCountryId(),
                        'phoneNumber' => $shippingAddress->getTelephone()
                    )
                )
                ->setShippingAmount(
                    $this->parseAmount($this->quote->collectTotals()->getTotals()['shipping']->getData('value')), //TODO
                    $currency
                )
                ->setMerchantReference($this->quote->getId());

            if (!empty($discountAmount)) {
                $createCheckoutRequest->setDiscounts(
                    array(
                        array(
                            'displayName' => 'Clearpay Discount coupon',
                            'amount'      => array($this->parseAmount($discountAmount), $currency)
                        )
                    )
                );
            }

            $items     = $this->quote->getAllVisibleItems();
            $products  = array();
            $itemArray = array();
            foreach ($items as $key => $item) {
                $itemArray[] = $item;
                $products[]  = array(
                    'name'     => $item->getName(),
                    'sku'      => $item->getSku(),
                    'quantity' => (int)$item->getQty(),
                    'price'    => array(
                        $this->parseAmount($item->getPrice()),
                        $currency
                    )
                );
            }
            $checkProductCategories = $this->category->allowedCategories($itemArray);
            if (!empty($checkProductCategories)) {
                $this->logger->debug($checkProductCategories);
                throw new \Exception($checkProductCategories);
            }

            $createCheckoutRequest->setItems($products);

            $moduleInfo = $this->moduleList->getOne('Clearpay_Clearpay');
            //EX:MyClearpayModule/1.0.0 (E-Commerce Platform Name/1.0.0; PHP/7.0.0; Merchant/60032000) https://ex.com
            $header = sprintf(
                'Magento2/%s (Magento/%s; PHP/%s; Merchant/%s %s',
                $moduleInfo['setup_version'],
                $this->productMetadataInterface->getVersion(),
                phpversion(),
                $this->config->getMerchantId(),
                $this->_url->getUrl()
            );
            $createCheckoutRequest->addHeader('User-Agent', $header);
            $createCheckoutRequest->addHeader('Country', $countryCode);

            $url = $cancelUrl;
            if ($createCheckoutRequest->isValid()) {
                $createCheckoutRequest->send();
                if (isset($createCheckoutRequest->getResponse()->getParsedBody()->errorCode)) {
                    $this->logger->error($createCheckoutRequest->getResponse()->getParsedBody()->message);
                    throw new \Exception($createCheckoutRequest->getResponse()->getParsedBody()->message);
                } else {
                    $orderId = $createCheckoutRequest->getResponse()->getParsedBody()->token;
                    $url     = $createCheckoutRequest->getResponse()->getParsedBody()->redirectCheckoutUrl;

                    $this->insertRow($this->quote->getId(), $orderId, $token, $countryCode);
                }
            } else {
                $this->logger->error($createCheckoutRequest->getValidationErrors());
                throw new \Exception($createCheckoutRequest->getValidationErrors());
            }

            echo $url;
            exit;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            echo $cancelUrl;
            exit;
        }
    }

    /**
     * @return string|null
     */
    private function getCountryCode()
    {
        $countryCode = null;
        if ($this->config->getApiRegion() === 'GB') {
            $allowedCountries = array('0'=>'gb');
        } else {
            $allowedCountries = unserialize($this->extraConfig['CLEARPAY_ALLOWED_COUNTRIES']);
        }
        $shippingCountry = $this->quote->getShippingAddress()->getCountry();
        $billingCountry  = $this->quote->getBillingAddress()->getCountry();
        $haystack  = ($this->store->getLocale()!=null) ? $this->store->getLocale() : $this->getResolverCountry();
        $langCountry = strtolower(strstr($haystack, '_', true));
        $countryCode = (in_array($langCountry, $allowedCountries)) ? ($langCountry) :
            ((in_array(strtolower($shippingCountry), $allowedCountries)) ? ($shippingCountry) :
                ((in_array(strtolower($billingCountry), $allowedCountries))? ($billingCountry) :
                    (null))
            );

        return strtoupper($countryCode);
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    private function parseAmount($string)
    {
        return $string;
    }

    /**
     * @param $quoteId
     * @param $clearpayOrderId
     * @param $token
     * @param $countryCode
     *
     * @return int
     */
    private function insertRow($quoteId, $clearpayOrderId, $token, $countryCode)
    {
        $dbConnection = $this->dbObject->getConnection();
        $tableName = $this->dbObject->getTableName(self::ORDERS_TABLE);
        return $dbConnection->insert(
            $tableName,
            array('id'=>$quoteId,'order_id'=>$clearpayOrderId,'token'=>$token,'country_code'=>$countryCode),
            array('order_id')
        );
    }

    /**
     * @return array
     */
    private function getMetadata()
    {
        $magentoVersion = $this->productMetadataInterface->getVersion();
        $moduleInfo = $this->moduleList->getOne('Clearpay_Clearpay');
        return array(
            'cp_module' => 'magento2x',
            'cp_version' => $moduleInfo['setup_version'],
            'ec_module' => 'magento',
            'ec_version' => $magentoVersion
        );
    }

    /**
     * @return mixed
     */
    private function getResolverCountry()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('Magento\Framework\Locale\Resolver');

        if (method_exists($store, 'getLocale')) {
            return $store->getLocale();
        }

        return null;
    }
}
