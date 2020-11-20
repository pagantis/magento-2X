<?php

namespace Pagantis\Pagantis\Controller\Payment;

use Afterpay\SDK\Model;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Checkout\Model\Session;
use Pagantis\Pagantis\Helper\Config;
use Pagantis\Pagantis\Helper\ExtraConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Api\Data\StoreInterface;
use Afterpay\SDK\HTTP\Request\CreateCheckout;
use Afterpay\SDK\MerchantAccount;

/**
 * Class Index
 * @package Pagantis\Pagantis\Controller\Payment
 */
class Index extends Action
{
    /** Orders tablename */
    const ORDERS_TABLE = 'cart_process';

    /** Concurrency tablename */
    const LOGS_TABLE = 'Pagantis_logs';

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
        StoreInterface $storeInterface
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
    }

    public function execute()
    {
        Model::setAutomaticValidationEnabled(true);
        $createCheckoutRequest = new CreateCheckout();
        $clearpayMerchantAccount = new MerchantAccount();
        $countryCode = $this->getCountryCode();
        $clearpayMerchantAccount
            ->setMerchantId($this->config->getMerchantId())
            ->setSecretKey($this->config->getSecretKey())
            ->setApiEnvironment($this->config->getApiEnvironment())
        ;

        if (!is_null($countryCode)) {
            $clearpayMerchantAccount->setCountryCode($countryCode);
        }

        $metadata = $this->getMetadata();
        $uriRoute = 'pagantis/notify/index';
        $urlToken = strtoupper(md5(uniqid(rand(), true)));
        $token = md5($urlToken);
        $cancelUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);

        $quoteId = $this->quote->getId();
        if (version_compare($metadata['pg_version'], '2.3.0') >= 0) {
            $uriRoute = 'pagantis/notify/indexV2';
        }
        $okUrl     = $this->_url->getUrl($uriRoute, ['_query' => ['quoteId'=>$quoteId, 'token'=>$token]]);
        $currency = $this->quote->getCurrency()->getBaseCurrencyCode();
        $shippingAddress = $this->quote->getShippingAddress();
        $billingAddress = $this->quote->getBillingAddress();

        $customer = $this->quote->getCustomer();
        $params = $this->getRequest()->getParams();
        $email = '';
        if (isset($params['email']) && $params['email']!='') {
            $this->session->setEmail($params['email']); //Get guest email after refresh page
            $customer->setEmail($params['email']);
            $this->quote->setCheckoutMethod('guest');
            $this->quote->getBillingAddress()->setEmail($params['email']);
            $email = $params['email'];
        } elseif ($customer->getEmail()=='') {
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
            ->setMerchant(array(
                'redirectConfirmUrl' => $okUrl,
                'redirectCancelUrl' => $cancelUrl
            ))
            ->setMerchantAccount($clearpayMerchantAccount)
            ->setTotalAmount(
                $this->parseAmount($this->quote->getGrandTotal()),
                $currency
            )
            ->setTaxAmount(
                $this->parseAmount($this->quote->getTaxAmount()),
                $currency
            )
            ->setConsumer(array(
                'phoneNumber' => $billingAddress->getTelephone(),
                'givenNames' => $shippingAddress->getFirstname(),
                'surname' => $shippingAddress->getLastname(),
                'email' => $email
            ))
            ->setBilling(array(
                'name' => $billingAddress->getFirstname() . " " . $billingAddress->getLastname(),
                'line1' => $billingAddress->getStreetFull(),
                'line2' => $billingAddress->getStreetLine(2),
                'suburb' => $billingAddress->getCity(),
                'state' => $billingAddress->getCountry(),
                'postcode' => $billingAddress->getPostcode(),
                'countryCode' => $billingAddress->getCountryId(),
                'phoneNumber' => $billingAddress->getTelephone()
            ))
            ->setShipping(array(
                'name' => $shippingAddress->getFirstname() . " " . $shippingAddress->getLastname(),
                'line1' => $shippingAddress->getStreetFull(),
                'line2' => $shippingAddress->getStreetLine(2),
                'suburb' => $shippingAddress->getCity(),
                'state' => $shippingAddress->getCountry(),
                'postcode' => $shippingAddress->getPostcode(),
                'countryCode' => $shippingAddress->getCountryId(),
                'phoneNumber' => $shippingAddress->getTelephone()
            ))
            ->setShippingAmount(
                $this->parseAmount($this->quote->collectTotals()->getTotals()['shipping']->getData('value')), //TODO
                $currency
            );

        if (!empty($discountAmount)) {
            $createCheckoutRequest->setDiscounts(array(
                array(
                    'displayName' => 'Clearpay Discount coupon',
                    'amount' => array($this->parseAmount($discountAmount), $currency)
                )
            ));
        }

        $items = $this->quote->getAllVisibleItems();
        $products = array();
        foreach ($items as $key => $item) {
            $products[] = array(
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'quantity' => $item->getQty(),
                'price' => array(
                    $this->parseAmount($item->getPrice()),
                    $currency
                )
            );
        }
        $createCheckoutRequest->setItems($products);

        $moduleInfo = $this->moduleList->getOne('Pagantis_Pagantis');
        //EX:MyClearpayModule/1.0.0 (E-Commerce Platform Name/1.0.0; PHP/7.0.0; Merchant/60032000) https://merchant.com
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
                throw new \Exception($createCheckoutRequest->getResponse()->getParsedBody()->message);
            } else {
                $orderId = $createCheckoutRequest->getResponse()->getParsedBody()->token;
                $url = $createCheckoutRequest->getResponse()->getParsedBody()->redirectCheckoutUrl;

                $this->insertRow($this->quote->getId(), $orderId, $token, $countryCode);
            }
        } else {
            throw new \Exception($createCheckoutRequest->getValidationErrors());
        }

        echo $url;
        exit;
    }

    /**
     * @return string|null
     */
    private function getCountryCode()
    {
        $countryCode = null;
        $allowedCountries = unserialize($this->extraConfig['PAGANTIS_ALLOWED_COUNTRIES']);
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
     * Get the orders of a customer
     * @param $customerId
     *
     * @return array
     */
    private function getOrders($customerId)
    {
        $orderCollection = array();
        if ($customerId!='') {
            $this->orderCollection->addAttributeToFilter('customer_id', $customerId)
                            ->addAttributeToFilter(
                                'status',
                                ['in' => ['processing','pending','complete']]
                            )
                            ->load();
            $orderCollection = $this->orderCollection->getData();
        }
        return $orderCollection;
    }

    /**
     * @param $quoteId
     * @param $pagantisOrderId
     * @param $token
     * @param $countryCode
     *
     * @return int
     */
    private function insertRow($quoteId, $pagantisOrderId, $token, $countryCode)
    {
        $dbConnection = $this->dbObject->getConnection();
        $tableName = $this->dbObject->getTableName(self::ORDERS_TABLE);
        return $dbConnection->insert(
            $tableName,
            array('id'=>$quoteId,'order_id'=>$pagantisOrderId,'token'=>$token,'country_code'=>$countryCode),
            array('order_id')
        );
    }

    /**
     * @return array
     */
    private function getMetadata()
    {
        $magentoVersion = $this->productMetadataInterface->getVersion();
        $moduleInfo = $this->moduleList->getOne('Pagantis_Pagantis');
        return array(
            'pg_module' => 'magento2x',
            'pg_version' => $moduleInfo['setup_version'],
            'ec_module' => 'magento',
            'ec_version' => $magentoVersion
        );
    }

    /**
     * @param $exceptionMessage
     */
    private function insertLog($exceptionMessage)
    {
        if ($exceptionMessage instanceof \Exception) {
            $logObject          = new \stdClass();
            $logObject->message = $exceptionMessage->getMessage();
            $logObject->code    = $exceptionMessage->getCode();
            $logObject->line    = $exceptionMessage->getLine();
            $logObject->file    = $exceptionMessage->getFile();
            $logObject->trace   = $exceptionMessage->getTraceAsString();

            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::LOGS_TABLE);
            $dbConnection->insert($tableName, array('log' => json_encode($logObject)));
        }
    }

    /**
     * @param $billingAdd
     *
     * @return null
     */
    private function getTaxId($billingAdd)
    {
        if (isset($billingAdd['vat_id'])) {
            return $billingAdd['vat_id'];
        } elseif (isset($billingAdd['cod_fisc'])) {
            return $billingAdd['cod_fisc'];
        } else {
            return null;
        }
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
