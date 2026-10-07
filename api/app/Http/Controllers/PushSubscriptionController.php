<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'endpoint'    => 'required',
            'keys.auth'   => 'required',
            'keys.p256dh' => 'required',
        ]);

        $request->user()->updatePushSubscription(
            $request->endpoint,
            $request->keys['p256dh'],
            $request->keys['auth']
        );

        return response()->json(['message' => 'Subscribed to push notifications.'], 201);
    }

    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => 'required']);

        $request->user()->deletePushSubscription($request->endpoint);

        return response()->json(['message' => 'Unsubscribed from push notifications.']);
    }

    public function vapidKey()
    {
        return response()->json(['vapid_public_key' => config('webpush.vapid.public_key')]);
    }
}
