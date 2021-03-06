<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Billable;

use App\Product;
use App\Order;
use App\Invoice;
use App\Invoices_item;
use App\User;
use App\Country;
use App\UsState;


class OrderController extends Controller
{
   
    
    public function __construct()
    {
        //$this->middleware('auth');
    }    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['orders'] = Order::getAllByUserId(Auth::user()->id);
        $data['page_title'] = 'Orders';        
        return view('orders.list', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
     
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {        
        //validate an accesss
        if( Order::checkClientOwner($id, Auth::user()->id) === false || !isset($id) )
        {
            Session::flash('msg_error', 'Sorry, the invoice that you are trying to access does not belong to you.');
            return redirect('/home');
        }           
        $user = User::find(Auth::user()->id);        
        
        $data['pk_stripe'] = config('services.stripe.key');        
                
        $data['invoice'] = Invoice::where('order_id', $id)
                ->orderBy('order_id', 'desc')->first();
        $data['order'] = Order::where('order_id', $id)->first();
                
        $data['page_title'] = 'Order';
        return view('orders.view', $data); 
    }

    public function showServerDetailsByOrderId($id)
    {
        
        //validate an accesss
        if( Order::checkClientOwner($id, Auth::user()->id) === false || !isset($id) )
        {
            Session::flash('msg_error', 'Sorry, the invoice that you are trying to access does not belong to you.');
            return redirect('/home');
        }   

        $data['invoice'] = Invoice::where('order_id', $id)->first();
        $data['order'] = Order::where('order_id', $id)->first();
                
        $data['page_title'] = 'Server Details';
        return view('orders.view_server', $data); 
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    
    public function confirmCancel($id)
    {
        if( Order::checkClientOwner($id, Auth::user()->id) === false || !isset($id) )
        {
            Session::flash('msg_error', 'Sorry, the order that you are trying to access does not belong to you.');
            return redirect('/home');
        }          
        $data['user'] = User::find(Auth::user()->id);
        $data['order'] = Order::where('order_id', $id)->first();
        $data['page_title'] = 'Order Cancellation';  
        //return view('home', $data);
        return view('orders.cancel', $data);   
    } 
    public function cancelNow($id)
    {
        if( Order::checkClientOwner($id, Auth::user()->id) === false || !isset($id) )
        {
            Session::flash('msg_error', 'Sorry, the order that you are trying to access does not belong to you.');
            return redirect('/home');
        }                  
        $order = Order::find($id);
        $order->order_status = 'c';
        $order->save();
        $invoice = Invoice::where('order_id', $order->order_id)->first();
        StripeController::cancelSubscription($invoice->stripe_subscription_id);
        Session::flash('msg', 'Order ID '.$id.' was cancelled.');
        return redirect('/home');        
    }      
}
