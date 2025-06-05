<?php

use Carbon\Carbon;

function getStatusWord($status)
{
    if ($status == 0) {
        return "Pending";
    } elseif ($status == 1) {
        return "Successful";
    } elseif ($status == 3) {
        return "Insufficient Balance";
    } elseif ($status == 4) {
        return "Payment Error";
    } elseif ($status == 5) {
        return "Dormant/Blocked";
    } elseif ($status == 6) {
        return "Unregistered";
    } else {
        return "Failed";
    }
}

function getProductDeliveryStatusBadges($paymentProductDeliveries, $paymentSuccessful, $createdAt)
{
    $dateOfDeliveryFeature = Carbon::parse('2024-10-06 18:20:00'); // Second date

    if ($paymentSuccessful && $createdAt->greaterThan($dateOfDeliveryFeature) && ($paymentProductDeliveries == null || $paymentProductDeliveries->count() == 0)) {
        return '<br><span class="badge text-bg-danger"> No sensor data <i class="fa fa-circle-xmark"></i></span>&nbsp;';
    }

    $badges = "";
    foreach ($paymentProductDeliveries as $deliveryStatus) {

        $text =  'Lane : '. str_replace('.', '-', $deliveryStatus->lane_id) . ' Remaining : ' . $deliveryStatus->lane_quantity . '/' . $deliveryStatus->inventoryState->max_quantity;

        if ($deliveryStatus->status_code == 2000) {
            $badges .= '<br><span class="badge text-bg-success"> '.  $text .' <i class="fa fa-check-circle"></i></span>&nbsp;';
        } elseif ($deliveryStatus->status_code == 4001) {
            $badges .= '<br><span class="badge text-bg-dark" data-bs-toggle="tooltip" title="Lane turned but item did not drop"> '. $text .' <i class="fa fa-triangle-exclamation"></i></span>&nbsp;';
        } else {
            $badges .= '<br><span class="badge text-bg-secondary" data-bs-toggle="tooltip" title="Status Code : '. $deliveryStatus->status_code . '"> '. $text .' <i class="fa fa-circle-xmark"></i></span>&nbsp;';
        }
    }

    return $badges;
}
