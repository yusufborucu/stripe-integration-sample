<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redirect;

class PaymentController extends Controller
{
    public function index()
    {
        return view('payment');
    }

    public function store()
    {
        // kart bilgilerini alıyoruz.
        $holder_name = request()->holder_name;
        $card_number = request()->card_number;
        $month = request()->month;
        $year = request()->year;
        $cvc = request()->cvc;

        // örnek olarak karttan 1$ çekeceğiz. stripe dokümanında belirtildiği üzere karttan çekilecek değeri 100 ile çarpmamız gerekiyor.
        $currency = 'USD';
        $price = 1 * 100;

        // stripe secret_key bilgisini config'den alıyoruz.
        $stripe = new \Stripe\StripeClient(
            config('stripe.secret_key')
        );        

        try {
            // kart bilgilerini kullanarak stripe tarafında bir token elde ediyoruz.
            $stripeToken = $stripe->tokens->create([
                'card' => [
                    'number' => $card_number,
                    'exp_month' => $month,
                    'exp_year' => $year,
                    'cvc' => $cvc
                ]
            ]);

            // müşteri adını ve kart bilgisini stripe tarafında kaydediyoruz.
            $customer = $stripe->customers->create([
                'name' => $holder_name,
                'source' => $stripeToken['id']
            ]);

            // stripe tarafında bir setupIntent oluşturuyoruz. 
            // setupIntent işlemi sonucunda stripe, girilen kartın 3ds gerektirip gerektirmediğiyle ilgili bir doğrulama yapıyor.
            // stripe'ın ödeme sonucunu iletmesi için 'return_url' parametresini belirtiyoruz.
            // 3ds ekranı sonrası para çekme işleminde kullanmak için 'metadata' kısmında fiyat ve para birimi değerlerini gönderiyoruz.
            $setupIntent = $stripe->setupIntents->create([
                'customer' => $customer['id'],
                'description' => 'test',
                'payment_method' => $stripeToken['card']['id'],
                'payment_method_types' => ['card'],
                'payment_method_options' => [
                    'card' => [
                        'request_three_d_secure' => 'any'
                    ]
                ],
                'confirm' => true,
                'return_url' => config('stripe.merchant_url') .'/stripe-3ds-result',
                'metadata' => [
                    'price' => $price,
                    'currency' => $currency
                ]
            ]);
        }
        // hata oluşması durumunda hata mesajını json olarak ekrana yazdırıyoruz.
        catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json($e->getMessage(), 500);
        }

        // eğer doğrulama sonucunda ödeme normal (3ds olmadan) gerçekleşecekse direkt olarak para çekme işlemini yapıyoruz.
        if ($setupIntent['status'] == 'succeeded') {
            try {
                $charge = $stripe->charges->create([
                    'customer' => $setupIntent['customer'],
                    'amount' => $price,
                    'currency' => $currency,
                    'description' => 'test',
                    'source' => $stripeToken['card']['id']
                ]);
            } 
            // hata oluşması durumunda hata mesajını json olarak ekrana yazdırıyoruz.
            catch (\Stripe\Exception\ApiErrorException $e) {
                return response()->json($e->getMessage(), 500);
            }

            // para çekme işleminde bir sorun olursa ekrana hata yazdırıyoruz.
            if ($charge['status'] != 'succeeded') {
                return response()->json('Payment error!', 500);
            } else {
                // para çekme işlemi başarılı sonuçlanırsa stripe'dan dönen veriyi ekrana basıyoruz.
                return response()->json($charge, 200);
            }            
        }

        // eğer doğrulama sonucunda ödeme 3ds ile gerçekleşecekse kullanıcıyı stripe'ın gönderdiği 3ds ekranına yönlendiriyoruz.
        if ($setupIntent['status'] == 'requires_action') {
            return Redirect::to($setupIntent['next_action']['redirect_to_url']['url']);
        } else {
            // setupIntent işleminde bir sorun olursa ekrana hata yazdırıyoruz.
            return response()->json('3ds payment error!', 500);
        }        
    }

    public function stripe3dsResult()
    {
        $stripe = new \Stripe\StripeClient(
            config('stripe.secret_key')
        );

        // stripe tarafından gönderilen setupIntent id'yi kullanarak setupIntent'e ulaşıyoruz.
        $setupIntent = $stripe->setupIntents->retrieve(
            request()->setup_intent
        );        

        // 'metadata' ile gönderdiğimiz fiyat ve para birimi değerlerine ulaşıyoruz.
        $price = $setupIntent['metadata']['price'];
        $currency = $setupIntent['metadata']['currency'];

        // setupIntent'in sonucu başarılı ise para çekme işlemini yapıyoruz.
        if ($setupIntent['status'] == 'succeeded') {            
            try {
                $charge = $stripe->charges->create([
                    'customer' => $setupIntent['customer'],
                    'amount' => $price,
                    'currency' => $currency,
                    'description' => 'test',
                    'source' => $setupIntent['payment_method']
                ]);
            } 
            // hata oluşması durumunda hata mesajını json olarak ekrana yazdırıyoruz.
            catch (\Stripe\Exception\ApiErrorException $e) {
                return response()->json($e->getMessage(), 500);
            }

            // para çekme işleminde bir sorun olursa ekrana hata yazdırıyoruz.
            if ($charge['status'] != 'succeeded') {
                return response()->json('3ds payment error!', 500);
            } else {
                // para çekme işlemi başarılı sonuçlanırsa stripe'dan dönen veriyi ekrana basıyoruz.
                return response()->json($charge, 200);
            }
        } else {
            // setupIntent işleminde bir sorun olursa ekrana hata yazdırıyoruz.
            return response()->json('3ds payment error!', 500);
        }
    }
}