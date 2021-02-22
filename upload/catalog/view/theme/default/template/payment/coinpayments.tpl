<?php if(isset($error_coinpayments)){ ?>
<div class="warning" style="">
    <?php echo $error_coinpayments ?>
</div>
<?php } else { ?>
<form action="<?php echo $action ?>" method="POST">
    Pay with Bitcoin, Litecoin, or other altcoins via  <a href="https://alpha.coinpayments.net/" target="_blank" title="CoinPayments.net">CoinPayments.net</a>
    <div class="buttons">
        <div class="pull-right">
            <input type="submit" value="<?php echo $button_confirm ?>" class="button"/>
        </div>
    </div>
</form>
<?php } ?>
