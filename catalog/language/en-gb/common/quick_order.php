<?php
// "Buy in one click" — the popup (common/quick_order.twig) and the e-mail to the
// manager (common/quick_order/order). The button on the cards and the product
// page is button_quick_order in the global en-gb.php.

// Popup
$_['text_eyebrow']        = 'Quick order';
$_['heading_title']       = 'Buy in one click';
$_['text_lead']           = 'Leave your name and phone number — our manager will call you back, confirm the details and place the order for you.';
$_['text_quantity']       = 'Quantity: %s';
$_['text_close']          = 'Close';
$_['text_note']           = 'A name and a phone number are enough — the rest is agreed on the call.';

// Fields
$_['entry_name']          = 'Name';
$_['entry_phone']         = 'Phone';
$_['placeholder_name']    = 'How should we address you';
$_['placeholder_phone']   = '+380';

// Buttons
$_['button_submit']       = 'Order';
$_['button_done']         = 'Continue shopping';

// Success screen
$_['text_done_title']     = 'Thank you!';
$_['text_done']           = 'Your request has been received. Our manager will call you shortly to confirm the details.';
$_['text_success']        = 'Request sent — we will call you back.';

// Mail to the manager (route=common/quick_order/order)
$_['text_mail_subject']   = 'Buy in one click: %s';
$_['text_mail_title']     = 'New quick order from %s (buy in one click).';
$_['text_mail_product']   = 'Product:';
$_['text_mail_model']     = 'SKU:';
$_['text_mail_price']     = 'Price:';
$_['text_mail_quantity']  = 'Quantity:';
$_['text_mail_total']     = 'Total:';
$_['text_mail_link']      = 'Product link:';
$_['text_mail_customer']  = 'Name:';
$_['text_mail_phone']     = 'Phone:';
$_['text_mail_account']   = 'Account:';
$_['text_mail_guest']     = 'guest';
$_['text_mail_language']  = 'Interface language:';
$_['text_mail_page']      = 'Ordered from page:';
$_['text_mail_date']      = 'Date:';
$_['text_mail_ip']        = 'IP:';

// Errors
$_['error_name']          = 'Enter your name (2 to 64 characters).';
$_['error_phone']         = 'Enter a valid phone number.';
$_['error_product']       = 'Product not found — refresh the page and try again.';
$_['error_stock']         = 'Sorry, this product is currently out of stock.';
$_['error_too_often']     = 'The request was already sent. Wait half a minute before sending again.';
$_['error_send']          = 'Could not send the request. Please give us a call.';
$_['error_network']       = 'Could not reach the server. Please try again.';
