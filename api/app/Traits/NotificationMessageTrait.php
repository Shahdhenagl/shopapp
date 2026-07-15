<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait NotificationMessageTrait {
  function getMessage($notification) {
    if (Str::contains($notification->type, 'DashboardNotification')) {
      return $this->data['message'];
    } else {
      return $this->transTypeToMessage($notification);
    }
  }

  public function transTypeToMessage($notification, $local = null) {
    $transData = [];

    $type = ltrim($notification->type, 'App\\Notifications\\');

    if (isset($notification->data['order_num'])) {
      $transData['order_num'] = $notification->data['order_num'];
    }

    if (isset($notification->data['amount'])) {
      $transData['amount'] = $notification->data['amount'];
    }

    $msg = trans('notification.' . $type, $transData, $local);
    return $msg;
  }
}
