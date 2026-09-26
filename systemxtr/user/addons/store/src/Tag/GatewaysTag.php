<?php

namespace Store\Tag;

use Store\Model\PaymentMethod;

class GatewaysTag extends AbstractTag
{
    public function parse()
    {
        // Get all enabled payment methods for this site
        $methods = PaymentMethod::where('site_id', config_item('site_id'))
            ->where('enabled', 1)
            ->orderBy('title')
            ->get();

        $tag_vars = [];
        $count = 1;
        foreach ($methods as $method) {
            $tag_vars[] = [
                'gateway_short_name' => $method->class,
                'gateway_title' => $method->title,
                'count' => $count,
                'selected_gateway' => ($this->param('selected') == $method->class ? 'selected' : ''),
            ];
            $count++;
        }

        if (empty($tag_vars)) {
            return $this->no_results('gateways');
        }

        return $this->parse_variables($tag_vars);
    }
}
