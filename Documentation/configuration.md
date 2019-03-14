# Configuration

## :house: Access

To access to Pagantis admin panel, we need to open the Magento admin panel and follow the next steps:

1 – STORES => Configuration
![Step 1](./magento21_step1.png?raw=true "Step 1")

2 – SALES => Payment Methods
![Step 2](./magento21_step2.png?raw=true "Step 2")

3 – Pagantis
![Step 3](./magento21_step3.png?raw=true "Step 3")

## :clipboard: Options
In Pagantis admin panel, we can set the following options:

| Field &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;| Description<br/><br/>
| :------------- |:-------------| 
| Enabled      | - Yes => Module enabled<br/> - No => Módule disabled (Por defecto)
| Public API Key(*) |  String you can get from your [Pagantis profile](https://bo.pagantis.com/shop).
| Secret API Key(*) |  String you can get from your [Pagantis profile](https://bo.pagantis.com/shop). 
| Product Simulator    |  Choose if we want to use installments simulator inside product page.

## :clipboard: Advanced configuration:
The module has many configuration options you can set, but we recommend use it as is.

If you want to manage it, you have a way to update the values via HTTP, you only need to make a post to:

<strong>{your-domain-url}/pagantis/Payment/Config?secret={your-secret-key}</strong>

sending in the form data the key of the config you want to change and the new value.


Here you have a complete list of configurations you can change and it's explanation. 


| Field | Description<br/><br/>
| :------------- |:-------------| 
| PAGANTIS_TITLE                           | Payment title to show in checkout page. By default:"Instant financing".
| PAGANTIS_SIMULATOR_DISPLAY_TYPE          | Installments simulator skin inside product page, in positive case. Recommended value: 'pmtSDK.simulator.types.SIMPLE'.
| PAGANTIS_SIMULATOR_DISPLAY_SKIN          | Skin of the product page simulator. Recommended value: 'pmtSDK.simulator.skins.BLUE'.
| PAGANTIS_SIMULATOR_DISPLAY_POSITION      | Choose the place where you want to watch the simulator.
| PAGANTIS_SIMULATOR_START_INSTALLMENTS    | Number of installments by default to use in simulator.
| PAGANTIS_SIMULATOR_DISPLAY_CSS_POSITION  | he position where the simulator widget will be injected. Recommended value: 'pmtSDK.simulator.positions.INNER'.
| PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR    | CSS selector with DOM element having totalAmount value.
| PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR | CSS Selector to inject the widget. (Example: '#simulator', '.PmtSimulator')
| PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR | CSS selector with DOM element having the quantity selector value.
| PAGANTIS_FORM_DISPLAY_TYPE               | Allow you to select the way to show the payment form in your site
| PAGANTIS_DISPLAY_MIN_AMOUNT              | Minimum amount to use the module and show the payment method in the checkout page.
| PAGANTIS_URL_OK                          | Location where user will be redirected after a successful payment. This string will be concatenated to the base url to build the full url
| PAGANTIS_URL_KO                          | Location where user will be redirected after a wrong payment. This string will be concatenated to the base url to build the full url 
 
