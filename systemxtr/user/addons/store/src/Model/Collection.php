<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Model;

use Store\Dependency\Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Collection extends EloquentCollection
{
    /**
     * Convert the collection of items to a template tag variables array
     *
     * @param null $count_prefix
     * @return array
     */
    public function toTagArray($count_prefix = null)
    {
        $count = 1;
        $total = count($this->items);

        $tag_vars = array_map(function ($item) use ($count_prefix, &$count, $total) {
            /** @var AbstractModel $item */
            $item_vars = $item->toTagArray();

            if ($count_prefix) {
                $item_vars["$count_prefix:count"] = $count++;
                $item_vars["$count_prefix:total_results"] = $total;
            }

            return $item_vars;
        }, $this->items);

        // work around EE template parser nested empty tags bug
        return empty($tag_vars) ? [[]] : $tag_vars;
    }
}
