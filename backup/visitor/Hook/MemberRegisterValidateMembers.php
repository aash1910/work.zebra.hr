<?php

namespace EEHarbor\Visitor\Hook;

use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * MemberRegisterValidateMembers Hook Class
 *
 * @package         EEHarbor_Visitor
 * @author          EEHarbor <https://www.eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <https://www.parscale.com>
 * @license         https://www.eeharbor.com/license/
 * @link            https://www.eeharbor.com
 */
class MemberRegisterValidateMembers extends AbstractHook
{

    /**
     * Additional processing when member(s) are self validated
     *
     * @param  int  $member_id    The ID of the member
     * @return void
     */
    public function execute($member_id)
    {
        $group_id_col = version_compare(APP_VER, '6.0', '>=') ? 'role_id' : 'group_id';
        ee()->db->select('group_id');
        $query = ee()->db->get_where('visitor_activation_membergroup', array('member_id' => $member_id));

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                ee()->db->where('member_id', $member_id);
                ee()->db->update('members', array($group_id_col => $row->group_id));

                // Delete the record
                ee()->db->delete('visitor_activation_membergroup', array('member_id' => $member_id));
            }
        }

        ee('visitor:Members')->syncMemberStatus($member_id);
    }
}

/* End of file MemberRegisterValidateMembers.php */
/* Location: ./system/user/addons/Visitor/Hook/MemberRegisterValidateMembers.php */
