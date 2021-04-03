<?php

return [
  'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
  'secret_key' => env('STRIPE_SECRET_KEY'),
  'merchant_url' => env('APP_URL')
];