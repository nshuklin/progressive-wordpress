<?php

namespace nicomartin\ProgressiveWordPress;

class PushSubscriptions
{
    public static $subscriptions_option = 'pwp-push-subscriptions';

    public function run()
    {
        self::maybeUpdateSubscriptions();
        add_filter('pwp_submenu_push', [$this, 'addSubmenuItem']);
        add_action('rest_api_init', [$this, 'registerRoute']);
    }

    public function addSubmenuItem($items)
    {
        $items['subscriptions'] = __('Push Subscriptions', 'progressive-wp');

        return $items;
    }

    public function registerRoute()
    {
        register_rest_route(pwpGetInstance()->api_namespace, 'push-subscription', [
            'methods'  => 'GET',
            'callback' => [$this, 'apiGetPushSubscription'],
        ]);

        register_rest_route(pwpGetInstance()->api_namespace, 'push-subscriptions', [
            'methods'             => 'GET',
            'callback'            => [$this, 'apiGetPushSubscriptions'],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);

        register_rest_route(pwpGetInstance()->api_namespace, 'push-subscription', [
            'methods'  => 'PUT',
            'callback' => [$this, 'apiPutPushSubscription'],
        ]);

        register_rest_route(pwpGetInstance()->api_namespace, 'push-subscription/(?P<subscriptionId>\S+)', [
            'methods'  => 'DELETE',
            'callback' => [$this, 'apiDeletePushSubscription'],
            'args'     => [
                'subscriptionId'
            ],
        ]);
    }

    public function apiGetPushSubscription()
    {
        return [];
    }

    public function apiGetPushSubscriptions()
    {
        return array_map(
            function ($subscription) {
                $subscription['time']         = date('c', $subscription['time']);
                $user_data                    = get_userdata($subscription['wp_user']);
                $subscription['wp_user']      = $user_data ? $user_data->data->display_name : '';
                $subscription['subscription'] = null;

                return $subscription;
            },
            self::getPushSubscriptions()
        );
    }

    public function apiPutPushSubscription($req)
    {
        $params       = $req->get_params();
        $subscription = self::updateSubscription(
            $params['subscriptionId'],
            $params['subscription'],
            [
                'clientdata' => $params['clientdata']
            ]
        );

        if (is_wp_error($subscription)) {
            return $subscription;
        }

        return true;
    }

    public function apiDeletePushSubscription($req)
    {
        $subscriptionId = $req->get_param('subscriptionId');
        $deleted        = self::removeSubscription($subscriptionId);

        return $deleted;
    }

    /**
     * @return array
     */
    private static function getPushSubscriptions()
    {
        return get_option(self::$subscriptions_option, []);
    }

    public static function getPushSubscriptionsByIds($ids = [])
    {
        $subs = self::getPushSubscriptions();
        if (count($ids) === 0) {
            return $subs;
        }

        return array_filter($subs, function ($sub) use ($ids) {
            return in_array($sub['id'], $ids);
        });
    }

    private function maybeUpdateSubscriptions()
    {
        $v2_subscriptions = get_option('pwp-webpush-subscriptions', []);
        if (count($v2_subscriptions) === 0) {
            return;
        }

        foreach ($v2_subscriptions as $subscription) {
            self::updateSubscription(
                md5($subscription['subscription']->endpoint),
                (array)$subscription['subscription'],
                [
                    'wp_user'    => $subscription['wp_user'],
                    'clientdata' => $subscription['clientdata'],
                    'groups'     => $subscription['groups'],
                    'time'       => $subscription['time']
                ]
            );
        }

        update_option('pwp-webpush-subscriptions', []);
    }

    private static function removeSubscription($subscriptionId)
    {
        $subscriptions = self::getPushSubscriptions();
        $to_save       = [];
        if ( ! array_key_exists($subscriptionId, $subscriptions)) {
            return new \WP_Error(
                'subscription_id_does_not_exist',
                __('No subscription found that matches the given id', 'progressive-wp')
            );
        }

        foreach ($subscriptions as $id => $subscription) {
            if ($id !== $subscriptionId) {
                $to_save[$id] = $subscription;
            }
        }
        update_option(self::$subscriptions_option, $to_save);

        return true;
    }

    public static function getGroups()
    {
        return apply_filters('pwp_push_groups', []);
    }

    private static function updateSubscription(
        $subscriptionId,
        $subscription = null,
        $subscriptionData = []
    ) {
        $subscriptions = self::getPushSubscriptions();
        if ( ! array_key_exists($subscriptionId, $subscriptions)) {
            if ($subscription === null) {
                return new \WP_Error('invalid_push_subscription', __('invalid push subscription', 'progressive-wp'));
            }
            $subscriptions[$subscriptionId] = [
                'id'           => $subscriptionId,
                'subscription' => $subscription,
                'wp_user'      => 0,
                'time'         => time(),
                'clientdata'   => [],
                'groups'       => [],
            ];
        }

        foreach (['wp_user', 'clientdata', 'groups', 'time'] as $dataKey) {
            if (array_key_exists($dataKey, $subscriptionData)) {
                $subscriptions[$subscriptionId][$dataKey] = $subscriptionData[$dataKey];
            }
        }

        update_option(self::$subscriptions_option, $subscriptions);

        return $subscriptions;
    }
}
