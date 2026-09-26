<?php

namespace EEHarbor\ChannelImages\Fieldtypes;

use Expressionengine\Coilpack\FieldtypeOutput;
use Expressionengine\Coilpack\Models\FieldContent;
use Expressionengine\Coilpack\Support\Parameter;
use GraphQL\Type\Definition\Type;
use Expressionengine\Coilpack\Fieldtypes\Generic;

use EEHarbor\ChannelImages\Model\Image;
use EEHarbor\ChannelImages\model\channel_images_model;


class ChannelImages extends Generic
{

    public function apply(FieldContent $content, array $parameters = [])
    {
        ee()->load->add_package_path(PATH_THIRD . 'channel_images/');
        ee()->load->model('channel_images_model');

        ee()->TMPL->set_data([]);
        ee()->channel_images_model->parse_template($content->entry_id, $content->field->field_id, $parameters, '');

        return FieldtypeOutput::for($this)->value(['files' => ee()->TMPL->get_data()]);
    }


}