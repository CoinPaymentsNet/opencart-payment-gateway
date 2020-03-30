<?php if(isset($error_coinpayments)){ ?>
<div class="alert alert-danger alert-dismissible">
    <i class="fa fa-exclamation-circle"></i> <?php echo $error_coinpayments ?>
</div>
<?php } else { ?>

<form id="coinpayments_submit" name="coinpayments_submit" action="<?php echo $url_action ?>" method="GET">
    <?php foreach($form_params  as $key => $value){ ?>
    <input type="hidden" name="<?php echo $key ?>" value="<?php echo $value ?>"/>
    <?php } ?>
</form>
<div class="buttons">
    <div class="pull-right">
        <input type="button" value="<?php echo $button_confirm ?>" id="button-confirm" class="btn btn-primary"/>
    </div>
</div>
<script type="text/javascript"><!--
    $('#button-confirm').bind('click', function () {
        $('#coinpayments_submit').submit();
    });
    //--></script>
<?php } ?>
