<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Agreement;
use PayPal\Api\Plan;
use PayPal\Api\ShippingAddress;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Common\PayPalModel;
use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;

use App\Product;
use App\Invoice;

class InvoiceController extends Controller
{
    private $_apiContext;
    
    public function __construct()
    {
            //$this->middleware('auth');



            $this->_apiContext = new \PayPal\Rest\ApiContext(
                new \PayPal\Auth\OAuthTokenCredential(
                    env('PAYPAL_CLIENT_ID'),     // ClientID
                    env('PAYPAL_SECRET')      // ClientSecret
                )
            );

            /* LOG levels
             * 
             * Sandbox Mode
                DEBUG, INFO, WARN, ERROR.
                Please note that, DEBUG is only allowed in sandbox mode. It will throw a warning, and reduce the level to INFO if set in live mode.
                Live Mode
                INFO, WARN, ERROR
                DEBUG mode is not allowed in live environment. It will throw a warning, and reduce the level to INFO if set in live mode.
             */    

            
            $this->_apiContext->setConfig(
                array(
                    'mode' => 'sandbox',
                    'log.LogEnabled' => true,
                    'log.FileName' => storage_path('paypal.log'),
                    'http.ConnectionTimeOut' => 30,
                    'log.LogLevel' => 'DEBUG', // PLEASE USE FINE LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
                    'cache.enabled' => true,
                    // 'http.CURLOPT_CONNECTTIMEOUT' => 30
                    // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
                )
            );
             

            /*
            $this->_apiContext->setConfig(
                array(
                    'mode' => 'live',
                    'log.LogEnabled' => true,
                    'log.FileName' => storage_path('paypal.log'),
                    'log.LogLevel' => 'DEBUG',
                    'validation.level' => 'log',
                    'cache.enabled' => true,
                )
            );
            */

    }    
    
    
 
        /**
         * Redirects a user to PayPal
         * The instance of this Payment is into the "_constructor"
         * @param type $id
         * @return type
         */
        public function clientShowPayPalCheckout($id)
        {
            //validate an accesss
            if( Invoice::checkClientOwner($id, Auth::user()->id) === false || !isset($id) )
            {
                Session::flash('msg_error', 'Sorry, the invoice that you are trying to access does not belong to you.');
                return redirect('/home');
            }

            $payment_db = Invoice::find($id);            
            $items_invoice = Invoice::find($payment_db->invoice_id)->invoice_itens;
                        

// Create a new instance of Plan object
$plan = new Plan();

// # Basic Information
// Fill up the basic information that is required for the plan
$plan->setName('T-Shirt of the Month Club Plan')
    ->setDescription('Template creation.')
    ->setType('fixed');

// # Payment definitions for this billing plan.
$paymentDefinition = new PaymentDefinition();

// The possible values for such setters are mentioned in the setter method documentation.
// Just open the class file. e.g. lib/PayPal/Api/PaymentDefinition.php and look for setFrequency method.
// You should be able to see the acceptable values in the comments.
$paymentDefinition->setName('Regular Payments')
    ->setType('REGULAR')
    ->setFrequency('Month')
    ->setFrequencyInterval("2")
    ->setCycles("12")
    ->setAmount(new Currency(array('value' => 153.49, 'currency' => 'USD'))); // plan price

// Charge Models
/*
$chargeModel = new ChargeModel();
$chargeModel->setType('SHIPPING')
    ->setAmount(new Currency(array('value' => 10, 'currency' => 'USD')));

$paymentDefinition->setChargeModels(array($chargeModel));
*/
$merchantPreferences = new MerchantPreferences();
$baseUrl = url('/');
// ReturnURL and CancelURL are not required and used when creating billing agreement with payment_method as "credit_card".
// However, it is generally a good idea to set these values, in case you plan to create billing agreements which accepts "paypal" as payment_method.
// This will keep your plan compatible with both the possible scenarios on how it is being used in agreement.
$merchantPreferences->setReturnUrl("$baseUrl/ExecuteAgreement.php?success=true")
    ->setCancelUrl("$baseUrl/ExecuteAgreement.php?success=false")
    ->setAutoBillAmount("yes")
    ->setInitialFailAmountAction("CONTINUE")
    ->setMaxFailAttempts("0")
    ->setSetupFee(new Currency(array('value' => 1, 'currency' => 'USD')));


$plan->setPaymentDefinitions(array($paymentDefinition));
$plan->setMerchantPreferences($merchantPreferences);

// For Sample Purposes Only.
$request = clone $plan;


///###########@@@@@@@@

// ### Create Plan
try {
    $output = $plan->create($this->_apiContext);    
    
    print_r($output);
    //return Redirect::to($request);
} catch (Exception $ex) {
    // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
    var_dump($ex);
    exit(1);
}
            
            

            //end
            
        }        
            
        
        /* PayPal */


        public function getPayPalPaymentStatus()
        {
            // Get the payment ID before session clear
            $payment_id = Session::get('paypal_invoice_id');
            $dbinvoice_id = Session::get('paypal_dbinvoice_id');

            // clear the session payment ID
            Session::forget('paypal_payment_id');
            Session::forget('paypal_dbinvoice_id');

            if (empty(Input::get('PayerID')) || empty(Input::get('token'))) {

                $data['message'] = 'Payment failed, nothing was posted.';
                $data['page_title'] = 'Payment Status';
                $data['approved'] = 0;
                return view('invoices/status', $data);

                /* return Redirect::route('api/payment/status/show')
                    ->with('error', 'Payment failed'); */
            }

            $payment = Payment::get($payment_id, $this->_apiContext);

            // PaymentExecution object includes information necessary
            // to execute a PayPal account payment.
            // The payer_id is added to the request query parameters
            // when the user is redirected from paypal back to your site
            $execution = new PaymentExecution();
            $execution->setPayerId(Input::get('PayerID'));

            //Execute the payment
            $result = $payment->execute($execution, $this->_apiContext);

            //echo '<pre>';print_r($result);echo '</pre>'; // DEBUG RESULT, remove it later

            if ($result->getState() == 'approved') { // payment made

                /* change Paypal status */
                $tmp_pmt = Invoice::find($dbinvoice_id);
                $tmp_pmt->inv_status = 'p';
                $tmp_pmt->paid_date = date('Y-m-d');
                $tmp_pmt->save();
                /* END change Paypal status */

                
                /*
                 * 
                 *  TODO: cPanel integration here 
                 * 
                 */                
                
                //send e-mail
                $data_email = [                    
                    "invoice_id" => $tmp_pmt->invoice_id
                ];

                Mail::send('emails.new_receipt', $data_email, function($message) use ($data_email)
                {
                    $message->to('arthur@catandmouse.co', 'Arthur')->subject('New Payment Confirmed');
                    $message->to('elle@catandmouse.co', 'Elle')->subject('New Payment Confirmed');
                });
                

                 
                /* END Send e-mail to managers */
                $data['message'] = 'We processed your payment successfully. Our team will contact you soon.';
                $data['page_title'] = 'Payment Status';
                $data['approved'] = 1;
                return view('invoices/status', $data);
            }
                $data['approved'] = 0;
                $data['message'] = 'Payment failed';
                $data['page_title'] = 'Payment Status';
                return view('invoices/status', $data);
        }        
            
        public function showPaymentCancel()
        {
            $data['page_title'] = 'Invoice - Canceled';
            return view('invoices/canceled', $data);
        }    
        
        
    public function showClientInvoices()
    {
        $data['invoices'] = Invoice::where('user_id', Auth::user()->id)->orderBy('invoice_id', 'desc')->get();
        $data['page_title'] = 'Invoices';        
        return view('invoices.list', $data);
    }    

    public function showClientInvoiceById($id)
    {
        //validate an accesss
        if( Invoice::checkClientOwner($id, Auth::user()->id) === false || !isset($id) )
        {
            Session::flash('msg_error', 'Sorry, the invoice that you are trying to access does not belong to you.');
            return redirect('/home');
        }        
        
        $data['invoice'] = Invoice::find($id);
        $data['invoice_itens'] = Invoice::find($id)->invoice_itens;
        $data['page_title'] = 'Invoices';        
        return view('invoices.view', $data);
    }    
    
    public function jsonGetPricesByProduct()
    {
        $product = Product::find(Request::query('id'));


        $data["anually_price"] = $product->price_year;
        $data["anually_monthly_price"] =  round($product->price_year/12,2);
        $data["monthly_price"] = $product->price_month;
        $data["product_name"] = $product->prod_name;

        return json_encode($data);
    }  
    
    public function jsonGetCycleByType()
    {
        if(Request::query('type') == 'monthly'){
            $date = HelperController::returnNextMonth(date('Y-m-d'), 2);
        }
        else {
            $date = HelperController::returnNextYear(date('Y-m-d'), 2);
        }


        return json_encode($date);
    }     
        
}
