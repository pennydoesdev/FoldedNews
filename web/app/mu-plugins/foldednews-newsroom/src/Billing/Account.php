<?php

namespace FoldedNews\Newsroom\Billing;

/**
 * Reader access state. WordPress is the source of truth for identity and access;
 * subscription status is mirrored from Stripe via webhooks into user meta.
 */
final class Account
{
    public const CUSTOMER_META = '_fn_stripe_customer';
    public const STATUS_META = '_fn_subscription_status';
    public const PERIOD_META = '_fn_period_end';
    public const COMP_META = '_fn_comp_access';

    /** Subscription states that grant member access. */
    private const ACTIVE = ['active', 'trialing'];

    public static function isMember(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if ((bool) get_user_meta($userId, self::COMP_META, true)) {
            return true; // editorial / comp access
        }

        return in_array((string) get_user_meta($userId, self::STATUS_META, true), self::ACTIVE, true);
    }

    public static function setStatus(int $userId, string $status, int $periodEnd = 0): void
    {
        if ($userId <= 0) {
            return;
        }

        update_user_meta($userId, self::STATUS_META, sanitize_text_field($status));

        if ($periodEnd > 0) {
            update_user_meta($userId, self::PERIOD_META, $periodEnd);
        }
    }

    public static function linkCustomer(int $userId, string $customerId): void
    {
        if ($userId > 0 && $customerId !== '') {
            update_user_meta($userId, self::CUSTOMER_META, $customerId);
        }
    }

    public static function findUserByCustomer(string $customerId): int
    {
        if ($customerId === '') {
            return 0;
        }

        $users = get_users([
            'meta_key' => self::CUSTOMER_META,
            'meta_value' => $customerId,
            'number' => 1,
            'fields' => 'ID',
        ]);

        return $users !== [] ? (int) $users[0] : 0;
    }
}
