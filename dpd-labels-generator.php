<?php
/**
 * Plugin Name: DPD Labels Generator
 * Description: Génère et fusionne les étiquettes DPD pour les commandes WooCommerce.
 * Version: 1.0.0
 * Author: Aurélien Cabirol
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'config.php';

use setasign\Fpdi\TcpdfFpdi;

function initialize_soap_client() {
    $client = new SoapClient(DPD_WSDL_URL);

    $auth = [
        'userid' => DPD_USERID,
        'password' => DPD_PASSWORD,
    ];

    $header = new SOAPHeader(DPD_SOAP_NAMESPACE, 'UserCredentials', $auth);
    $client->__setSoapHeaders($header);

    return $client;
}

add_action('bulk_actions-edit-shop_order', function($bulk_actions) {
    $bulk_actions['generate_dpd_labels'] = 'Générer les étiquettes DPD';
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-shop_order', function($redirect_to, $action, $order_ids) {
    if ($action !== 'generate_dpd_labels') return $redirect_to;

    $plugin_dir = plugin_dir_path(__FILE__);
    $temp_dir = $plugin_dir . 'temp/';
    if (!file_exists($temp_dir)) mkdir($temp_dir, 0777, true);

    $counter = 1;
    $pdf_files = [];

    $soap_client = initialize_soap_client();

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);

        if (strpos(strtolower($order->get_shipping_method()), 'dpd') === false) {
            continue;
        }

        $pdf_content = generate_dpd_label($order, $soap_client);
        if ($pdf_content) {
            $pdf_path = $temp_dir . $counter . '.pdf';
            file_put_contents($pdf_path, $pdf_content);
            $pdf_files[] = $pdf_path;
            $counter++;
        }
    }

    if (empty($pdf_files)) return $redirect_to;

    $merged_pdf_path = $temp_dir . 'etiquettes-dpd-' . date('d-m-Y') . '.pdf';
    merge_pdfs($pdf_files, $merged_pdf_path);

    foreach ($pdf_files as $file) unlink($file);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="etiquettes-dpd-' . date('d-m-Y') . '.pdf"');

    readfile($merged_pdf_path);
    unlink($merged_pdf_path);

    exit;
}, 10, 3);

function generate_dpd_label($order, $soap_client) {
    $receiveraddress = [
        'name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
        'countryPrefix' => $order->get_shipping_country(),
        'zipCode' => $order->get_shipping_postcode(),
        'city' => $order->get_shipping_city(),
        'street' => $order->get_shipping_address_1(),
        'phoneNumber' => $order->get_billing_phone(),
    ];

    $shipperaddress = [
        'name' => get_bloginfo('name'),
        'countryPrefix' => WC()->countries->get_base_country(),
        'zipCode' => WC()->countries->get_base_postcode(),
        'city' => WC()->countries->get_base_city(),
        'street' => WC()->countries->get_base_address(),
        'phoneNumber' => WOOCOMMERCE_STORE_PHONE_NUMBER,
    ];


    $services = [];

    $shipping_method_id = get_shipping_method_id($order);

    if ($shipping_method_id === 'dpdfrance_predict') {
        $services['contact'] = [
            'type' => 'Predict',
            'sms' => $order->get_billing_phone(),
            'email' => $order->get_billing_email(),
        ];
    }

    if ($shipping_method_id === 'dpdfrance_relais') {
        $relay_id = get_dpd_relay_id($order);

        if (!empty($relay_id)) {
            $services['parcelshop'] = [
                'shopaddress' => [
                    'shopid' => $relay_id,
                ],
            ];
        }
    }

    $shipment_request = [
        'labelType' => [
            'type' => 'PDF',
        ],
        'receiveraddress' => $receiveraddress,
        'shipperaddress' => $shipperaddress,
        'services' => $services,
        'customer_countrycode' => 250,
        'customer_centernumber' => 77,
        'customer_number' => 18028,
        'referencenumber' => $order->get_id(),
        'weight' => get_order_total_weight($order),
    ];

    try {
        $response = $soap_client->CreateShipmentWithLabelsBc(['request' => $shipment_request]);
        $pdf_content = $response->CreateShipmentWithLabelsBcResult->labels->Label->label;
        return $pdf_content;
    } catch (SoapFault $e) {
        return null;
    }
}

function merge_pdfs($pdf_files, $output_path) {
    $pdf = new TcpdfFpdi();
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    foreach ($pdf_files as $file) {
        $pageCount = $pdf->setSourceFile($file);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplIdx = $pdf->importPage($i);
            $pdf->AddPage();
            $pdf->useTemplate($tplIdx);
        }
    }

    $pdf->Output($output_path, 'F');

    if (!file_exists($output_path) || filesize($output_path) === 0) {
        die("Erreur : Le fichier fusionné est vide ou corrompu.");
    }
}

function get_shipping_method_id($order) {
    $shipping_methods = $order->get_shipping_methods();

    if (empty($shipping_methods)) {
        return null;
    }

    $shipping_method = reset($shipping_methods);

    return $shipping_method ? $shipping_method->get_method_id() : null;
}


function get_dpd_relay_id($order) {
    $shipping_address_2 = $order->get_shipping_address_2();

    if (preg_match('/\((P\d+)\)$/', $shipping_address_2, $matches)) {
        return $matches[1];
    }

    return null;
}

function get_order_total_weight($order) {
    $total_weight = 0;

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product) {
            $total_weight += $product->get_weight() * $item->get_quantity();
        }
    }

    return $total_weight;
}