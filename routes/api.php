<?php


use PayPal\Api\Item;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Payment;
use PayPal\Api\ItemList;
use PayPal\Api\WebProfile;
use PayPal\Api\InputFields;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use Illuminate\Http\Request;
use PayPal\Api\PaymentExecution;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('create-payment', function () {
    $apiContext = new \PayPal\Rest\ApiContext(
        new \PayPal\Auth\OAuthTokenCredential(
            'AXrjLwZ1In_7lkc3kKVqwX-mV98_YjdYIO10J6WmXdnKVBQ2Umv66TOJnUOSHAm8fPi_shdHX9vItc-8',     // ClientID
            'EMTtSYIw4obqnK_oL0mB2nuS21TNQRTyLdnB8QfSxdTeOMdJKWWcE5bdmRTtkJr1TrsNfAM2TLyx3BXV'      // ClientSecret
        )
    );

    $payouts = new \PayPal\Api\Payout();
// This is how our body should look like:
    /*
     *{
        "sender_batch_header": {
            "sender_batch_id": "random_uniq_id",
            "email_subject": "You have a payment"
        },
        "items": [
            {
                "recipient_type": "EMAIL",
                "amount": {
                    "value": 0.99,
                    "currency": "USD"
                },
                "receiver": "shirt-supplier-one@mail.com",
                "note": "Thank you.",
                "sender_item_id": "item_1"
            },
            {
                "recipient_type": "EMAIL",
                "amount": {
                    "value": 0.90,
                    "currency": "USD"
                },
                "receiver": "shirt-supplier-two@mail.com",
                "note": "Thank you.",
                "sender_item_id": "item_2"
            },
            {
                "recipient_type": "EMAIL",
                "amount": {
                    "value": 2.00,
                    "currency": "USD"
                },
                "receiver": "shirt-supplier-three@mail.com",
                "note": "Thank you.",
                "sender_item_id": "item_3"
            }
        ]
    }
     */
    $senderBatchHeader = new \PayPal\Api\PayoutSenderBatchHeader();
// ### NOTE:
// You can prevent duplicate batches from being processed. If you specify a `sender_batch_id` that was used in the last 30 days, the batch will not be processed. For items, you can specify a `sender_item_id`. If the value for the `sender_item_id` is a duplicate of a payout item that was processed in the last 30 days, the item will not be processed.
// #### Batch Header Instance
    $senderBatchHeader->setSenderBatchId(uniqid())
        ->setEmailSubject("You have a payment");
// #### Sender Item
// Please note that if you are using single payout with sync mode, you can only pass one Item in the request
    $senderItem1 = new \PayPal\Api\PayoutItem();
    $senderItem1->setRecipientType('Email')
        ->setNote('Thanks you.')
        ->setReceiver('shirt-supplier-one@gmail.com')
        ->setSenderItemId("item_1" . uniqid())
        ->setAmount(new \PayPal\Api\Currency('{
                        "value":"0.99",
                        "currency":"USD"
                    }'));
// #### Sender Item 2
// There are many different ways of assigning values in PayPal SDK. Here is another way where you could directly inject json string.
    $senderItem2 = new \PayPal\Api\PayoutItem(
        '{
            "recipient_type": "EMAIL",
            "amount": {
                "value": 0.01,
                "currency": "USD"
            },
            "receiver": "r4gamia-buyer@gmail.com",
            "note": "Thank you.",
            "sender_item_id": "item_2"
        }'
    );
// #### Sender Item 3
// One more way of assigning values in constructor when creating instance of PayPalModel object. Injecting array.
    $senderItem3 = new \PayPal\Api\PayoutItem(
        array(
            "recipient_type" => "EMAIL",
            "receiver" => "r4gamia-buyer@gmail.com",
            "note" => "Thank you.",
            "sender_item_id" => uniqid(),
            "amount" => array(
                "value" => "0.01",
                "currency" => "USD"
            )
        )
    );
    $payouts->setSenderBatchHeader($senderBatchHeader)
        ->addItem($senderItem1)->addItem($senderItem2)->addItem($senderItem3);
// For Sample Purposes Only.
    $request = clone $payouts;
// ### Create Payout
    try {
        $output = $payouts->create(null, $apiContext);
    } catch (Exception $ex) {
        // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
        //ResultPrinter::printError("Created Batch Payout", "Payout", null, $request, $ex);
        exit(1);
    }
// NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
    //ResultPrinter::printResult("Created Batch Payout", "Payout", $output->getBatchHeader()->getPayoutBatchId(), $request, $output);
    return $output;


  /*  $payer = new Payer();
    $payer->setPaymentMethod("paypal");

    $item1 = new Item();
    $item1->setName('Ground Coffee 40 oz')
        ->setCurrency('USD')
        ->setQuantity(1)
        ->setSku("123123") // Similar to `item_number` in Classic API
        ->setPrice(7.5);
    $item2 = new Item();
    $item2->setName('Granola bars')
        ->setCurrency('USD')
        ->setQuantity(5)
        ->setSku("321321") // Similar to `item_number` in Classic API
        ->setPrice(2);

    $itemList = new ItemList();
    $itemList->setItems(array($item1, $item2));

    $details = new Details();
    $details->setShipping(1.2)
        ->setTax(1.3)
        ->setSubtotal(17.50);

    $amount = new Amount();
    $amount->setCurrency("USD")
        ->setTotal(20)
        ->setDetails($details);

    $transaction = new Transaction();
    $transaction->setAmount($amount)
        ->setItemList($itemList)
        ->setDescription("Payment description")
        ->setInvoiceNumber(uniqid());

    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl("http://laravel-paypal-example.test")
        ->setCancelUrl("http://laravel-paypal-example.test");

    // Add NO SHIPPING OPTION
    $inputFields = new InputFields();
    $inputFields->setNoShipping(1);

    $webProfile = new WebProfile();
    $webProfile->setName('test' . uniqid())->setInputFields($inputFields);

    $webProfileId = $webProfile->create($apiContext)->getId();

    $payment = new Payment();
    $payment->setExperienceProfileId($webProfileId); // no shipping
    $payment->setIntent("sale")
        ->setPayer($payer)
        ->setRedirectUrls($redirectUrls)
        ->setTransactions(array($transaction));

    try {
        $payment->create($apiContext);
    } catch (Exception $ex) {
        echo $ex;
        exit(1);
    }

    return $payment;*/
});

Route::post('execute-payment', function (Request $request) {
    $apiContext = new \PayPal\Rest\ApiContext(
        new \PayPal\Auth\OAuthTokenCredential(
            'AXrjLwZ1In_7lkc3kKVqwX-mV98_YjdYIO10J6WmXdnKVBQ2Umv66TOJnUOSHAm8fPi_shdHX9vItc-8',     // ClientID
            'EMTtSYIw4obqnK_oL0mB2nuS21TNQRTyLdnB8QfSxdTeOMdJKWWcE5bdmRTtkJr1TrsNfAM2TLyx3BXV'      // ClientSecret
        )
    );

    $paymentId = $request->paymentID;
    $payment = Payment::get($paymentId, $apiContext);

    $execution = new PaymentExecution();
    $execution->setPayerId($request->payerID);

    // $transaction = new Transaction();
    // $amount = new Amount();
    // $details = new Details();

    // $details->setShipping(2.2)
    //     ->setTax(1.3)
    //     ->setSubtotal(17.50);

    // $amount->setCurrency('USD');
    // $amount->setTotal(21);
    // $amount->setDetails($details);
    // $transaction->setAmount($amount);

    // $execution->addTransaction($transaction);

    try {
        $result = $payment->execute($execution, $apiContext);


    } catch (Exception $ex) {
        echo $ex;
        exit(1);
    }

    return $result;
});
