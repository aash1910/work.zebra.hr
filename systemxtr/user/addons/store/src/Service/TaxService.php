<?php

namespace Store\Service;

use Store\Model\OrderItem;
use Store\Model\Tax;

class TaxService extends AbstractService
{
    public function is_item_taxable(OrderItem $item, Tax $tax)
    {
        if ($tax->tax_override === true) {
            return true;
        }

        if ($item->tax_exempt == 1) {
            return false;
        }

        // Get category IDs from the relation, not a direct property
        $taxCategoryIds = null;
        if ($tax->relationLoaded('categories') && $tax->categories) {
            // Check if it's a collection or just an array
            if ($tax->categories instanceof \Illuminate\Support\Collection) {
                 $taxCategoryIds = $tax->categories->pluck('cat_id')->all();
            } elseif (is_array($tax->categories)) {
                 $taxCategoryIds = [];
                 foreach ($tax->categories as $category) {
                      if (is_object($category) && isset($category->cat_id)) {
                           $taxCategoryIds[] = $category->cat_id;
                      } elseif (is_array($category) && isset($category['cat_id'])) {
                           $taxCategoryIds[] = $category['cat_id'];
                      }
                 }
            }
        }

        // If the tax rule has no category restrictions, the item is taxable
        if (empty($taxCategoryIds)) {
            return true;
        }

        // If the item has no categories, it can't match the tax rule's categories
        if (empty($item->category_ids)) {
             return false;
        }

        // Check if item categories intersect with tax rule categories
        return !empty(array_intersect($item->category_ids, $taxCategoryIds));
    }
}
