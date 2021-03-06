<?php
namespace APP;
use \akou\DBTable;
class address extends \akou\DBTable
{
	var $id;
	var $type;
	var $name;
	var $email;
	var $rfc;
	var $user_id;
	var $address;
	var $zipcode;
	var $country;
	var $state;
	var $city;
	var $suburb;
	var $note;
	var $phone;
	var $created;
	var $updated;
}
class attachment extends \akou\DBTable
{
	var $id;
	var $uploader_user_id;
	var $file_type_id;
	var $filename;
	var $original_filename;
	var $content_type;
	var $size;
	var $width;
	var $height;
	var $status;
	var $created;
	var $updated;
}
class bank_account extends \akou\DBTable
{
	var $id;
	var $name;
	var $email;
	var $is_a_payment_method;
	var $user_id;
	var $created;
	var $updated;
	var $account;
	var $currency;
	var $alias;
	var $bank;
}
class bank_movement extends \akou\DBTable
{
	var $id;
	var $currency_id;
	var $transaction_type;
	var $card_ending;
	var $payment_id;
	var $received_by_user_id;
	var $client_user_id;
	var $provider_user_id;
	var $amount_received;
	var $total;
	var $type;
	var $reference;
	var $receipt_attachment_id;
	var $invoice_attachment_id;
	var $bank_account_id;
	var $note;
	var $paid_date;
	var $created;
	var $updated;
}
class bank_movement_bill extends \akou\DBTable
{
	var $id;
	var $bank_movement_id;
	var $bill_id;
	var $currency_id;
	var $currency_amount;
	var $exchange_rate;
	var $amount;
	var $created;
	var $updated;
}
class bank_movement_order extends \akou\DBTable
{
	var $id;
	var $bank_movement_id;
	var $order_id;
	var $currency_amount;
	var $amount;
	var $currency_id;
	var $exchange_rate;
	var $created;
	var $updated;
	var $created_by_user_id;
	var $updated_by_user_id;
}
class bill extends \akou\DBTable
{
	var $id;
	var $folio;
	var $accepted_status;
	var $organization_id;
	var $aproved_by_user_id;
	var $paid_by_user_id;
	var $bank_account_id;
	var $paid_to_bank_account_id;
	var $provider_user_id;
	var $purchase_order_id;
	var $invoice_attachment_id;
	var $pdf_attachment_id;
	var $receipt_attachment_id;
	var $note;
	var $due_date;
	var $paid_date;
	var $total;
	var $currency_id;
	var $amount_paid;
	var $status;
	var $paid_status;
	var $name;
	var $created;
	var $updated;
}
class box extends \akou\DBTable
{
	var $id;
	var $status;
	var $production_item_id;
	var $type_item_id;
	var $serial_number_range_start;
	var $serial_number_range_end;
	var $store_id;
	var $created;
	var $updated;
}
class box_content extends \akou\DBTable
{
	var $id;
	var $box_id;
	var $item_id;
	var $initial_qty;
	var $qty;
	var $serial_number_range_start;
	var $serial_number_range_end;
}
class brand extends \akou\DBTable
{
	var $id;
	var $image_id;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $name;
	var $description;
	var $created;
	var $updated;
}
class cart_item extends \akou\DBTable
{
	var $id;
	var $user_id;
	var $session_id;
	var $item_id;
	var $qty;
	var $type;
	var $created;
	var $updated;
}
class cash_close extends \akou\DBTable
{
	var $id;
	var $created_by_user_id;
	var $updated;
	var $since;
	var $created;
	var $start;
	var $end;
}
class category extends \akou\DBTable
{
	var $id;
	var $name;
	var $type;
	var $image_id;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class category_type extends \akou\DBTable
{
	var $id;
	var $TYPE;
}
class commanda extends \akou\DBTable
{
	var $id;
	var $commanda_type_id;
	var $name;
	var $store_id;
}
class commanda_type extends \akou\DBTable
{
	var $id;
	var $name;
	var $created;
	var $updated;
}
class currency extends \akou\DBTable
{
	var $id;
	var $name;
}
class file_type extends \akou\DBTable
{
	var $id;
	var $name;
	var $content_type;
	var $extension;
	var $is_image;
	var $image_id;
	var $created;
	var $updated;
}
class fund extends \akou\DBTable
{
	var $id;
	var $amount;
	var $currency_id;
	var $created;
	var $updated;
	var $created_by_user_id;
	var $cashier_hour;
}
class image extends \akou\DBTable
{
	var $id;
	var $uploader_user_id;
	var $is_private;
	var $filename;
	var $original_filename;
	var $content_type;
	var $size;
	var $width;
	var $height;
	var $created;
}
class item extends \akou\DBTable
{
	var $id;
	var $product_id;
	var $commanda_type_id;
	var $category_id;
	var $image_id;
	var $brand_id;
	var $provider_user_id;
	var $code;
	var $name;
	var $extra_name;
	var $note_required;
	var $on_sale;
	var $availability_type;
	var $description;
	var $reference_price;
	var $clave_sat;
	var $unidad_medida_sat_id;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class item_attribute extends \akou\DBTable
{
	var $id;
	var $item_id;
	var $name;
	var $value;
}
class item_option extends \akou\DBTable
{
	var $id;
	var $item_id;
	var $name;
	var $included_options;
	var $max_options;
	var $included_extra_qty;
	var $max_extra_qty;
	var $status;
}
class item_option_value extends \akou\DBTable
{
	var $id;
	var $item_id;
	var $item_option_id;
	var $included_qty;
	var $max_extra_qty;
	var $included_price;
	var $extra_price;
	var $charge_type;
	var $price;
	var $status;
}
class keyboard_shortcut extends \akou\DBTable
{
	var $id;
	var $name;
	var $key_combination;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class merma extends \akou\DBTable
{
	var $id;
	var $store_id;
	var $item_id;
	var $qty;
	var $created;
	var $created_by_user_id;
	var $updated;
	var $box_id;
	var $shipping_id;
	var $note;
}
class notification_token extends \akou\DBTable
{
	var $id;
	var $user_id;
	var $provider;
	var $token;
	var $created;
	var $updated;
	var $status;
}
class order extends \akou\DBTable
{
	var $id;
	var $client_user_id;
	var $cashier_user_id;
	var $store_id;
	var $shipping_address_id;
	var $billing_address_id;
	var $tax_percent;
	var $price_type_id;
	var $currency_id;
	var $status;
	var $paid_status;
	var $facturado;
	var $sat_xml_file_id;
	var $sat_rfc;
	var $sat_razon_social;
	var $sat_email;
	var $sat_pdf_id;
	var $sat_uso_cfdi;
	var $sat_codigo_postal;
	var $tag;
	var $paid_timetamp;
	var $client_name;
	var $service_type;
	var $delivery_status;
	var $total;
	var $discount;
	var $shipping_cost;
	var $subtotal;
	var $tax;
	var $amount_paid;
	var $address;
	var $suburb;
	var $city;
	var $state;
	var $created;
	var $system_activated;
	var $updated;
	var $authorized_by;
	var $note;
}
class order_item extends \akou\DBTable
{
	var $id;
	var $order_id;
	var $delivery_status;
	var $tax_included;
	var $delivered_qty;
	var $status;
	var $commanda_id;
	var $commanda_status;
	var $item_id;
	var $item_group;
	var $item_option_id;
	var $return_required;
	var $item_extra_id;
	var $is_item_extra;
	var $is_free_of_charge;
	var $note;
	var $price_id;
	var $qty;
	var $original_unitary_price;
	var $unitary_price;
	var $subtotal;
	var $discount;
	var $tax;
	var $total;
	var $preparation_status;
	var $system_preparation_started;
	var $system_preparation_ended;
	var $created_by_user_id;
	var $updated_by_user_id;
}
class pallet extends \akou\DBTable
{
	var $id;
	var $store_id;
	var $production_item_id;
	var $created;
	var $updated;
	var $created_by_user_id;
}
class pallet_content extends \akou\DBTable
{
	var $id;
	var $pallet_id;
	var $box_id;
	var $status;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class payment extends \akou\DBTable
{
	var $id;
	var $company_id;
	var $type;
	var $tag;
	var $concept;
	var $payment_amount;
	var $received_amount;
	var $change_amount;
	var $currency_id;
	var $exchange_rate;
	var $created_by_user_id;
	var $paid_by_user_id;
	var $created;
	var $updated;
}
class paypal_access_token extends \akou\DBTable
{
	var $id;
	var $access_token;
	var $raw_response;
	var $expires;
	var $created;
}
class paypal_order extends \akou\DBTable
{
	var $id;
	var $buyer_user_id;
	var $order_id;
	var $created;
	var $status;
	var $create_response;
	var $log;
}
class preferences extends \akou\DBTable
{
	var $id;
	var $name;
	var $default_product_image_id;
	var $default_price_type_id;
	var $default_ticket_image_id;
	var $logo_image_id;
	var $login_image_id;
	var $default_user_logo_image_id;
	var $default_file_logo_image_id;
	var $background_image_id;
	var $login_background_image_id;
	var $chat_upload_image_id;
	var $chat_upload_attachment_image_id;
	var $header_color;
	var $menu_subsection_color;
	var $menu_background_color;
	var $created;
	var $updated;
}
class preparation_area extends \akou\DBTable
{
	var $id;
	var $name;
	var $created;
	var $updated;
}
class price extends \akou\DBTable
{
	var $id;
	var $tax_included;
	var $price_list_id;
	var $currency_id;
	var $item_id;
	var $price_type_id;
	var $price;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class price_list extends \akou\DBTable
{
	var $id;
	var $name;
	var $created;
	var $updated;
	var $created_by_user_id;
	var $updated_by_user_id;
}
class price_type extends \akou\DBTable
{
	var $id;
	var $name;
	var $sort_priority;
	var $created;
	var $updated;
}
class product extends \akou\DBTable
{
	var $id;
	var $name;
}
class production_type extends \akou\DBTable
{
	var $id;
	var $to_produce_item_id;
}
class production_type_item extends \akou\DBTable
{
	var $id;
	var $production_type_item_id;
}
class push_notification extends \akou\DBTable
{
	var $id;
	var $user_id;
	var $object_type;
	var $object_id;
	var $priority;
	var $push_notification_id;
	var $sent_status;
	var $title;
	var $body;
	var $link;
	var $app_path;
	var $icon_image_id;
	var $read_status;
	var $response;
	var $created;
	var $updated;
}
class session extends \akou\DBTable
{
	var $id;
	var $user_id;
	var $status;
	var $created;
	var $updated;
}
class shipping extends \akou\DBTable
{
	var $id;
	var $shipping_guide;
	var $shiping_company;
	var $requisition_id;
	var $status;
	var $from_store_id;
	var $to_store_id;
	var $date;
	var $received_by_user_id;
	var $delivery_datetime;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class shipping_item extends \akou\DBTable
{
	var $id;
	var $shipping_id;
	var $requisition_item_id;
	var $item_id;
	var $box_id;
	var $pallet_id;
	var $qty;
	var $received_qty;
	var $shrinkage_qty;
	var $created;
	var $updated;
}
class stock_record extends \akou\DBTable
{
	var $id;
	var $item_id;
	var $order_item_id;
	var $store_id;
	var $shipping_item_id;
	var $production_item_id;
	var $serial_number_record_id;
	var $previous_qty;
	var $movement_qty;
	var $qty;
	var $description;
	var $movement_type;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class stocktake extends \akou\DBTable
{
	var $id;
	var $store_id;
	var $name;
	var $status;
	var $created;
	var $updated;
	var $created_by_user_id;
	var $updated_by_user_id;
}
class stocktake_item extends \akou\DBTable
{
	var $id;
	var $stocktake_id;
	var $box_id;
	var $box_content_id;
	var $pallet_id;
	var $item_id;
	var $creation_qty;
	var $current_qty;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class stocktake_scan extends \akou\DBTable
{
	var $id;
	var $stocktake_id;
	var $pallet_id;
	var $box_id;
	var $box_content_id;
	var $item_id;
	var $qty;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class store extends \akou\DBTable
{
	var $id;
	var $ticket_image_id;
	var $client_user_id;
	var $price_list_id;
	var $exchange_rate;
	var $name;
	var $ticket_footer_text;
	var $business_name;
	var $rfc;
	var $tax_percent;
	var $city;
	var $zipcode;
	var $state;
	var $address;
	var $phone;
	var $image_id;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class store_bank_account extends \akou\DBTable
{
	var $id;
	var $store_id;
	var $bank_account_id;
	var $name;
	var $created;
	var $updated;
}
class store_currency_rate extends \akou\DBTable
{
	var $id;
	var $store_id;
	var $first_currency_id;
	var $second_currency_id;
	var $rate;
}
class unidad_medida_sat extends \akou\DBTable
{
	var $id;
	var $nombre;
	var $descripcion;
}
class user extends \akou\DBTable
{
	var $id;
	var $default_shipping_address_id;
	var $default_billing_address_id;
	var $credit_days;
	var $price_type_id;
	var $store_id;
	var $name;
	var $credit_limit;
	var $username;
	var $phone;
	var $email;
	var $type;
	var $password;
	var $image_id;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
}
class user_permission extends \akou\DBTable
{
	var $user_id;
	var $add_items;
	var $send_shipping;
	var $receive_shipping;
	var $add_user;
	var $pos;
	var $preferences;
	var $caldos;
	var $store_prices;
	var $global_prices;
	var $add_stock;
	var $price_types;
	var $production;
	var $fullfill_orders;
	var $pay_bills;
	var $approve_bill_payments;
	var $add_bills;
	var $stocktake;
	var $add_marbetes;
	var $asign_marbetes;
	var $created_by_user_id;
	var $updated_by_user_id;
	var $created;
	var $updated;
	var $add_providers;
	var $add_requisition;
	var $global_bills;
	var $global_stats;
	var $is_provider;
}
