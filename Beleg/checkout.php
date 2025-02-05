<?php
// 1) Stripe-PHP laden
require('stripe-php-master/init.php');
\Stripe\Stripe::setApiKey(""); // <- Ihr Secret Key
try {
    // 3) Read the raw POST body (JSON from your Vue/JS fetch)
    $rawBody  = file_get_contents("php://input");
    $cartData = json_decode($rawBody, true);

    // $cartData might look like:
    // [
    //   { "name": "Buch A", "price": 15, "cartQuantity": 2 },
    //   { "name": "Buch B", "price": 20, "cartQuantity": 1 }
    // ]

    if (!is_array($cartData) || empty($cartData)) {
        throw new Exception("No cart items received.");
    }

    // 4) Convert your cart items to Stripe line_items
    $lineItems = [];
    foreach ($cartData as $item) {
        // IMPORTANT: Convert price to CENTS (e.g. 15.00 EUR -> 1500)
        $unitAmount = $item['price'] * 100;  

        // Prepare the Stripe line item structure with price_data
        $lineItems[] = [
            'price_data' => [
                'currency' => 'eur', 
                'unit_amount' => $unitAmount,
                'product_data' => [
                    'name' => $item['name']
                ],
            ],
            'quantity' => $item['cartQuantity'],
        ];
    }

    // 5) Create the Checkout Session
    error_log(print_r($lineItems, true)); // after building $lineItems
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items'          => $lineItems,
        'mode'                => 'payment',
        
        // Set your own success & cancel URLs (fully qualified URLs)
        'success_url'         => 'https://ivm108.informatik.htw-dresden.de/ewa/g39/Beleg/success.php',
        'cancel_url'          => 'https://ivm108.informatik.htw-dresden.de/ewa/g39/Beleg/cancel.php',
    ]);

    // 6) Return the session ID as JSON
    echo json_encode(['id' => $session->id]);

} catch (Exception $e) {
    // 7) Handle errors by returning a 500 (server error) and the message
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}