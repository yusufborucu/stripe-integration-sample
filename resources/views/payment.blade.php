<html>
  <head>
    <title>Stripe Integration Sample</title>
    <style>
      .form {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
      }
      .input {
        border-radius: 8px;
        padding: 8px;
        margin-bottom: 8px;
      }
      .button {
        padding: 8px;
        border-radius: 8px;
        background: green;
        color: white;
        width: 100px;
      }
    </style>
  </head>
  <body>
    <form action="{{ route('payment_post') }}" method="post">
      @csrf
      <div class="form">
        <input type="text" class="input" name="holder_name" placeholder="Holder Name" />
        <input type="text" class="input" name="card_number" placeholder="Card Number" />
        <input type="number" class="input" name="month" placeholder="Month" />
        <input type="number" class="input" name="year" placeholder="Year" />
        <input type="number" class="input" name="cvc" placeholder="Cvc" />
        <input type="submit" class="button" value="Pay" />
      </div>
    </form>
  </body>
</html>