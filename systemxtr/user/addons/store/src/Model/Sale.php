<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Model;

use Store\Dependency\Illuminate\Database\Eloquent\Casts\Attribute;

class Sale extends AbstractModel
{
    protected $table = 'store_sales';
    protected $fillable = [
        'name', 'start_date_str', 'end_date_str', 'member_group_ids', 'entry_ids', 'category_ids', 'per_item_discount',
        'percent_discount', 'notes', 'enabled',
    ];

    /**
     * Interact with the start date string attribute.
     */
    protected function startDateStr(): Attribute
    {
        return Attribute::make(
            get: function () {
                $timestamp = $this->attributes['start_date'] ?? null;
                if (empty($timestamp)) {
                    return null;
                }
                return date('Y-m-d h:i A', (int)$timestamp);
            },
            set: function ($value) {
                if (empty($value)) {
                    return ['start_date' => null];
                }

                if (is_numeric($value)) {
                    return ['start_date' => (int)$value];
                }

                $timestamp = strtotime($value);
                return ['start_date' => $timestamp === false ? null : $timestamp];
            }
        );
    }

    /**
     * Interact with the end date string attribute.
     */
    protected function endDateStr(): Attribute
    {
        return Attribute::make(
            get: function () {
                $timestamp = $this->attributes['end_date'] ?? null;
                if (empty($timestamp)) {
                    return null;
                }
                return date('Y-m-d h:i A', (int)$timestamp);
            },
            set: function ($value) {
                if (empty($value)) {
                    return ['end_date' => null];
                }

                if (is_numeric($value)) {
                    return ['end_date' => (int)$value];
                }

                $timestamp = strtotime($value);
                return ['end_date' => $timestamp === false ? null : $timestamp];
            }
        );
    }

    /**
     * Interact with the member group IDs attribute.
     */
    protected function memberGroupIds(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getPipeArrayAttribute('member_group_ids'),
            set: fn ($value) => ['member_group_ids' => $this->setPipeArrayForAttribute($value)]
        );
    }

    /**
     * Interact with the entry IDs attribute.
     */
    protected function entryIds(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getPipeArrayAttribute('entry_ids'),
            set: fn ($value) => ['entry_ids' => $this->setPipeArrayForAttribute($value)]
        );
    }

    /**
     * Interact with the category IDs attribute.
     */
    protected function categoryIds(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getPipeArrayAttribute('category_ids'),
            set: fn ($value) => ['category_ids' => $this->setPipeArrayForAttribute($value)]
        );
    }

    /**
     * Interact with the channel IDs attribute.
     */
    protected function channelIds(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getPipeArrayAttribute('channel_ids'),
            set: fn ($value) => ['channel_ids' => $this->setPipeArrayForAttribute($value)]
        );
    }

    /**
     * Interact with the per item discount attribute.
     */
    protected function perItemDiscount(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ['per_item_discount' => (float)$value ?: null]
        );
    }

    /**
     * Interact with the percent discount attribute.
     */
    protected function percentDiscount(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ['percent_discount' => (float)$value ?: null]
        );
    }

    /**
     * Helper method to convert pipe array for attribute setting
     */
    private function setPipeArrayForAttribute($value)
    {
        if(is_object($value)) {
            $value = $value->toArray();
        }

        $value = implode('|', array_filter((array)$value, function ($x) {
            return $x !== null && $x !== '';
        }));

        return $value === '' ? null : $value;
    }
}
