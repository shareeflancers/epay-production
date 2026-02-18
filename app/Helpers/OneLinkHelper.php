<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * 1Link API helper functions.
 *
 * Reusable formatting utilities for 1Link Inquiry & Payment responses.
 * Can be used from any model, controller, or service.
 */
class OneLinkHelper
{
    /**
     * Format amount into AN14 (+/- padded, last 2 digits = paisa).
     * Example: 120.00 -> +0000000012000
     */
    public static function formatAmount($amount): string
    {
        $sign = $amount >= 0 ? '+' : '-';
        $value = number_format(abs($amount) * 100, 0, '', '');
        return $sign . str_pad($value, 13, '0', STR_PAD_LEFT);
    }

    /**
     * Format a date into 1Link format (YYYYMMDD).
     * Accepts Carbon, DateTime, or string.
     */
    public static function formatDate($date): string
    {
        if (empty($date)) {
            return str_repeat(' ', 8);
        }

        if ($date instanceof Carbon || $date instanceof \DateTime) {
            return $date->format('Ymd');
        }

        return date('Ymd', strtotime($date));
    }

    /**
     * Derive billing_month from a date (YYMM + installment number).
     */
    public static function billingMonth($date, string $installment = '01'): string
    {
        if ($date instanceof Carbon || $date instanceof \DateTime) {
            return $date->format('ym') . $installment;
        }

        return date('ym', strtotime($date)) . $installment;
    }

    /**
     * Build a 1Link Inquiry Success Response array.
     */
    public static function inquiryResponse(array $params): array
    {
        return [
            'response_Code'         => '00',
            'consumer_Detail'       => $params['consumer_name'] ?? 'Student',
            'bill_status'           => $params['status'] ?? 'U',
            'due_date'              => self::formatDate($params['due_date'] ?? null),
            'amount_within_dueDate' => self::formatAmount($params['amount_within_dueDate'] ?? 0),
            'amount_after_dueDate'  => self::formatAmount($params['amount_after_dueDate'] ?? 0),
            'billing_month'         => self::billingMonth($params['due_date'] ?? now(), $params['installment'] ?? '01'),
            'date_paid'             => self::formatDate($params['date_paid'] ?? null),
            'amount_paid'           => !empty($params['date_paid'])
                                        ? self::formatAmount($params['amount_within_dueDate'] ?? 0)
                                        : str_repeat('0', 12),
            'tran_auth_Id'          => $params['tran_auth_id'] ?? '',
            'reserved'              => $params['reserved'] ?? '',
        ];
    }

    /**
     * Build a 1Link Payment Success Response array.
     */
    public static function paymentResponse(array $params): array
    {
        return [
            'response_Code'   => '00',
            'consumer_Detail'  => $params['tran_auth_id'] ?? '',
            'reserved'         => $params['reserved'] ?? '',
        ];
    }

    /**
     * Build a 1Link Error Response array.
     */
    public static function errorResponse(string $code, string $message): array
    {
        return [
            'response_Code' => $code,
            'message'       => $message,
        ];
    }
}
