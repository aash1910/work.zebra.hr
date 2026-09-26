<?php

namespace EEHarbor\Rating\Conduit;

use EEHarbor\Rating\FluxCapacitor\Conduit\McpNav as FluxNav;
use EEHarbor\Rating\Library\AddonBuilderClass;

class McpNav extends FluxNav
{
    protected function defaultItems($items = array())
    {
        $default_items = array(
            'entries' => lang('rated_entries'),
            'ratings' => lang('ratings'),
            'fields' => lang('fields'),
            'templates' => lang('notification_templates'),
            'preferences' => lang('preferences'),
            'utilities' => lang('utilities'),
            'code_pack' => lang('demo_templates'),
            'license' => lang('License'),
        );

        return array_merge($default_items, $items);
    }

    protected function defaultButtons()
    {
        return array(
            'fields' => array('field_form' => lang('new')),
            'templates' => array('template_form' => lang('new')),
        );
    }

    protected function defaultActiveMap()
    {
        return array(
            'field_form' => 'fields',
            'template_form' => 'templates',
        );
    }

    public function postGenerateNav()
    {
        $addonBuilder = new AddonBuilderClass();

        $mailto = 'mailto:mailto:help@eeharbor.com?&subject=' . $this->flux->getConfig('name') . ' support&body=Dear EEHarbor, We love your addons. I have a question about ' .
            $this->flux->getConfig('name') . ', version: ' . $this->flux->getConfig('version') .
            ' and I am running ExpressionEngine version ' . APP_VER . '.';

        $addonBuilder->set_nav(array(
            'resources'      => array(
                'title'    => lang('rating_resources'),
                'sub_list' => array(
                    'product_info'  => array(
                        'link'     => 'https://eeharbor.com/rating',
                        'title'    => lang('rating_product_info'),
                        'external' => true,
                    ),
                    'documentation' => array(
                        'link'     => 'https://eeharbor.com/rating/documentation',
                        'title'    => lang('rating_documentation'),
                        'external' => true,
                    ),
                    'support'       => array(
                        'link'     => $mailto,
                        'title'    => lang('rating_official_support'),
                        'external' => true,
                    ),
                ),
            ),
        ));
    }
}
