<form action="<?php echo $action; ?>" method="post">
  <input type="hidden" name="cmd" value="_pay" />
  <input type="hidden" name="reset" value="1" />
  <input type="hidden" name="merchant" value="<?php echo $merchant; ?>" />
  <input type="hidden" name="item_name" value="<?php echo $item_name; ?>" />
  <input type="hidden" name="currency" value="<?php echo $currency_code; ?>" />
  <input type="hidden" name="amountf" value="<?php echo $amount; ?>" />
  <input type="hidden" name="taxf" value="<?php echo $tax; ?>" />
  <input type="hidden" name="shippingf" value="<?php echo $shipping; ?>" />
  <input type="hidden" name="first_name" value="<?php echo $first_name; ?>" />
  <input type="hidden" name="last_name" value="<?php echo $last_name; ?>" />
  <?php if ($want_shipping) { ?>
  <input type="hidden" name="address1" value="<?php echo $address1; ?>" />
  <input type="hidden" name="address2" value="<?php echo $address2; ?>" />
  <input type="hidden" name="city" value="<?php echo $city; ?>" />
  <input type="hidden" name="state" value="<?php echo $state; ?>" />
  <input type="hidden" name="zip" value="<?php echo $zip; ?>" />
  <input type="hidden" name="country" value="<?php echo $country; ?>" />  
  <input type="hidden" name="phone" value="<?php echo $phone; ?>" />
  <?php } else { ?>
  <input type="hidden" name="want_shipping" value="0" />
  <?php } ?>
  <input type="hidden" name="email" value="<?php echo $email; ?>" />
  <input type="hidden" name="invoice" value="<?php echo $invoice; ?>" />
  <input type="hidden" name="success_url" value="<?php echo $success_url; ?>" />
  <input type="hidden" name="ipn_url" value="<?php echo $ipn_url; ?>" />
  <input type="hidden" name="cancel_url" value="<?php echo $cancel_url; ?>" />
  <input type="hidden" name="custom" value="<?php echo $custom; ?>" />
  <div class="buttons">
    <div class="right">
      <input type="submit" value="<?php echo $button_confirm; ?>" class="button" />
    </div>
  </div>
</form>
