<?php

namespace EEHarbor\Visitor\Hook;

/**
 * Abstract Hook Class
 *
 * @package         EEHarbor_Visitor
 * @author          EEHarbor <https://www.eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <https://www.parscale.com>
 * @license         https://www.eeharbor.com/license/
 * @link            https://www.eeharbor.com
 */
class AfterChannelEntryDelete extends AbstractHook
{

    /**
     * Called after the channel entry object is deleted.
     *
     * @param  object  $member   Current ChannelEntry model object
     * @param  array   $values   The ChannelEntry model object data as an array
     * @return void
     */
    public function execute($entry, $values)
    {
        if ($entry->channel_id != $this->settings['member_channel_id']) {
            return;
        }
        if ($this->settings['delete_member_when_deleting_entry'] != 'yes') {
            return;
        }

        $author_id = ee()->session->cache('visitor', 'author-' . $entry->entry_id);
        if (!$author_id) {
            return;
        }

        // Get the member_id
        $member = ee('Model')->get('Member', $author_id)->fields('member_id')->first();
        ;
        if (!$member) {
            return;
        }

        $member->delete();

        /* -------------------------------------------
        /* 'cp_members_member_delete_end' hook.
        /*  - Additional processing when a member is deleted through the CP
        */
        ee()->extensions->call('cp_members_member_delete_end', array($member));
        if (ee()->extensions->end_script === true) {
            return;
        }
        /*
        /* -------------------------------------------*/

        // Update
        ee()->stats->update_member_stats();
    }
}

/* End of file AfterChannelEntryDelete.php */
/* Location: ./system/user/addons/Visitor/Hook/AfterChannelEntryDelete.php */
