<!-- GETTING STARTED -->

## Getting Started

Follow these steps to get a local copy up and running.

### Prerequisites

You need to install composer and php before reaching the installation step.

### Installation

_Below is an example of how you can instruct your audience on installing and setting up your app. This template doesn't
rely on any external dependencies or services._

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
   ```
   DPD_WSDL_URL for testing : https://e-station-testenv.cargonet.software/eprintwebservice/eprintwebservice.asmx?WSDL \
   DPD_WSDL_URL for production : https://e-station.cargonet.software/dpd-eprintwebservice/eprintwebservice.asmx?WSDL