<?php

namespace EEHarbor\Tag\Commands;

use ExpressionEngine\Cli\Cli;
use EEHarbor\Tag\FluxCapacitor\Base\Mcp;

class UtilCommandSyncTags
{
    use \EEHarbor\Tag\Library\AddonBuilder;
}

class CommandSyncTags extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'backup';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'tag:backup';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'This command is to be used if a tag field is showing the tags correctly but somehow your front end is not.  What likely has happened is your data in the column tag_entries has been lost for some.  This will run through and regenerate that data relaying on what you have in your field (exp_data) and in your tag_tags table.  We have seen this happen either coming from publisher, or possibly if updating from an older version of tag when it had the tab and the sync did not somehow catch the tag_entries correctly.';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'This command is to be used if a tag field is showing the tags correctly but somehow your front end is not.  What likely has happened is your data in the column tag_entries has been lost for some.  This will run through and regenerate that data relaying on what you have in your field (exp_data) and in your tag_tags table.  We have seen this happen either coming from publisher, or possibly if updating from an older version of tag when it had the tab and the sync did not somehow catch the tag_entries correctly.';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php tag:backup';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [

    ];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        //$this->info('Hello World!');

        // This feels a bit of a weird way to set it up bit it works correctly, let's just kick this over to mod to deal with it there
        require_once PATH_THIRD.'tag/mod.tag.php'; 
        $tag = 'Tag';

        if (class_exists($tag) == false) {
            require $tag;
        }

        $tag_mod = new $tag();
        $tag_mod->test();
    }
}
