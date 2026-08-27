<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('inquiry-product-chat.{productId}', function ($user, $productId) {
    return true;
});

Broadcast::channel('chat.{type}.{id}', function ($user, $type, $id) {
    return true;
});

