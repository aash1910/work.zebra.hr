<?php

namespace Store\Update;

use Store\Update;

class Update500
{
    public function up()
    {
        Update::register_action('act_notification_handler', true);
    }
}
