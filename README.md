<!-- GETTING STARTED -->

## Getting Started

Follow these steps to get a local copy up and running.

### Prerequisites

You need to install composer and php before reaching the installation step.

### Installation

1. Install the php libraries
   ```sh
   composer install
   ```
    2. Create a config.php file at the root of the project and add the following code
       ```php
       <?php
       define('DPD_USERID', 'Your DPD API User ID');
       define('DPD_PASSWORD', 'Your DPD API Password');
       define('DPD_SOAP_NAMESPACE', 'http://www.cargonet.software');
       define('DPD_WSDL_URL', 'https://e-station-testenv.cargonet.software/eprintwebservice/eprintwebservice.asmx?WSDL');
       define('WOOCOMMERCE_STORE_PHONE_NUMBER', 'Your Store Phone Number');
       define('CENTER_NUMBER', 'Your DPD Center Number');
       define('DPD_CUSTOMER_NUMBER_RELAY', 'Your DPD Customer Relay Number');
       define('DPD_CUSTOMER_NUMBER_PREDICT', 'Your DPD Customer Predict Number');
       define('DPD_CUSTOMER_NUMBER_CLASSIC', 'Your DPD Customer Classic Number');
       
       ```
       DPD_WSDL_URL for
       testing : https://e-station-testenv.cargonet.software/eprintwebservice/eprintwebservice.asmx?WSDL \
       DPD_WSDL_URL for production : https://e-station.cargonet.software/dpd-eprintwebservice/eprintwebservice.asmx?WSDL