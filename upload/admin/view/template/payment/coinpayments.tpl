<?php echo $header; ?>
<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?><a
                href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>
    <?php if ($error_warning) { ?>
    <div class="warning"><?php echo $error_warning; ?></div>
    <?php } ?>
    <div class="box">
        <div class="heading">
            <h1><img src="view/image/payment.png" alt=""/> <?php echo $heading_title; ?></h1>
            <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a
                        onclick="location = '<?php echo $url_cancel; ?>';"
                        class="button"><?php echo $button_cancel; ?></a>
            </div>
        </div>
        <div class="content">
            <form action="<?php echo $url_action; ?>" method="post" enctype="multipart/form-data" id="form">
                <table class="form">
                    <tr>
                        <td><?php echo $entry_status; ?></td>
                        <td><select name="coinpayments_status">
                                <?php if ($coinpayments_status) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select></td>
                    </tr>
                    <tr>
                        <td><span class="required">*</span> <?php echo $entry_client_id; ?></td>
                        <td><input type="text" name="coinpayments_client_id"
                                   value="<?php echo $coinpayments_client_id; ?>"/>
                            <?php if ($error_client_id) { ?>
                            <span class="error"><?php echo $error_client_id; ?></span>
                            <?php } ?>
                            <?php if ($error_invalid_credentials) { ?>
                            <span class="error"><?php echo $error_invalid_credentials; ?></span>
                            <?php } ?>

                            <small class="form-text text-muted"><?php echo $help_client_id ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_webhooks; ?></td>
                        <td><select name="coinpayments_webhooks" id="input-webhooks">
                                <?php if ($coinpayments_webhooks) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                            <small class="form-text text-muted"><?php echo $help_webhooks ?></small>
                        </td>
                    </tr>
                    <tr id="input-client-secret-wrap"
                    <?php if ($coinpayments_webhooks != true){ ?>style="display: none;"<?php } ?>>
                    <td><span class="required">*</span> <?php echo $entry_client_secret; ?></td>
                    <td><input type="text" name="coinpayments_client_secret"
                               value="<?php echo $coinpayments_client_secret; ?>"/>
                        <?php if ($error_client_secret) { ?>
                        <span class="error"><?php echo $error_client_secret; ?></span>
                        <?php } ?>
                        <?php if ($error_invalid_credentials) { ?>
                        <span class="error"><?php echo $error_invalid_credentials; ?></span>
                        <?php } ?>
                        <small class="form-text text-muted"><?php echo $help_client_secret ?></small>
                    </td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_cancelled_status; ?></td>
                        <td><select name="coinpayments_cancelled_status">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php if ($order_status['order_status_id'] == $coinpayments_cancelled_status) { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"
                                        selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select></td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_completed_status; ?></td>
                        <td><select name="coinpayments_completed_status">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php if ($order_status['order_status_id'] == $coinpayments_completed_status) { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"
                                        selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select></td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_pending_status; ?></td>
                        <td><select name="coinpayments_pending_status">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php if ($order_status['order_status_id'] == $coinpayments_pending_status) { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"
                                        selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select></td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_geo_zone; ?></td>
                        <td><select name="coinpayments_geo_zone_id">
                                <option value="0"><?php echo $text_all_zones; ?></option>
                                <?php foreach ($geo_zones as $geo_zone) { ?>
                                <?php if ($geo_zone['geo_zone_id'] == $coinpayments_geo_zone_id) { ?>
                                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"
                                        selected="selected"><?php echo $geo_zone['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select></td>
                    </tr>
                    <tr>
                        <td><?php echo $entry_sort_order; ?></td>
                        <td><input type="text" name="coinpayments_sort_order"
                                   value="<?php echo $coinpayments_sort_order; ?>" size="1"/></td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>


<script>
    let webHooksSelector = document.getElementById('input-webhooks');
    let clientSecretWrap = document.getElementById('input-client-secret-wrap');
    if (webHooksSelector && clientSecretWrap) {
        webHooksSelector.onchange = function (e, o) {
            if (parseInt(e.target.value)) {
                clientSecretWrap.style.display = 'table-row';
            } else {
                clientSecretWrap.style.display = 'none';
            }
        };
    }
</script>
<?php echo $footer; ?> 