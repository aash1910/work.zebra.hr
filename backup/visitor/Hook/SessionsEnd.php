<?php

namespace EEHarbor\Visitor\Hook;

use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * Abstract Hook Class
 *
 * @package         EEHarbor_Visitor
 * @author          EEHarbor <https://www.eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <https://www.parscale.com>
 * @license         https://www.eeharbor.com/license/
 * @link            https://www.eeharbor.com
 */
class SessionsEnd extends AbstractHook
{

    /**
     * 'sessions_end' hook.
     * - Modify the user's session/member data.
     * - Additional Session or Login methods (ex: log in to other system)
     *
     * @param  obj $session The session object
     * @return void
     */
    public function execute($session)
    {
        if (REQ == 'CP') {
            $class  = ee()->router->class;
            $method = ee()->router->method;
            $uri = ee()->uri->uri_string();

            //dd($class, $method, $uri, $_GET, $_POST);

            // = Delete member entry if member is deleted =
            if ($class == 'members' && $method == 'delete') {
                // For now, when you delete a member it, it will also delete their entries, always!
                unset($_POST['heir_action']);
            }

            if ($uri == 'cp/members/groups/delete' && isset($_POST['replacement'])) {
                $this->_before_member_group_delete();
            }

            // Redirect "View All Members"
            if ($class == 'members' && $method == 'index' && $this->settings['redirect_view_all_members'] == 'yes') {
                $url = $this->getUrlFactory('cp/publish/edit', $session)->setQueryStringVariable('filter_by_channel', $this->settings['member_channel_id']);
                header("Location: {$url}");
                return;
            }

            // Redirect "Edit Member Profile"
            if ($class == 'profile' && $method == 'index' && $this->settings['redirect_member_edit_profile_to_edit_channel_entry'] == 'yes') {
                $entry_id = ee('visitor:Members')->getVisitorId(ee()->input->get('id'));

                if ($entry_id) {
                    $url = $this->getUrlFactory('cp/publish/edit/entry/' . $entry_id, $session);
                    header("Location: {$url}");
                    return;
                }
            }

            return;
        }
    }

    private function _before_member_group_delete()
    {
        $mgroupId = (ee()->input->post('replacement') == 'delete') ? 3 : $_POST['replacement'];

        foreach ($_POST['selection'] as $group_id) {
            // Grab all members with that group id
            $group_id_col = version_compare(APP_VER, '6.0', '>=') ? 'role_id' : 'group_id';
            $q = ee()->db->select('member_id')->from('members')->where($group_id_col, $group_id)->get();

            $ids = array();
            foreach ($q->result() as $row) {
                $ids[] = $row->member_id;
            }

            if (empty($ids)) {
                continue;
            }

            foreach ($ids as $member_id) {
                $entry_id = ee('visitor:Members')->getVisitorId($member_id);

                if ($entry_id) {
                    ee('visitor:Members')->updateMemberStatus($entry_id, $member_id, $mgroupId);
                }
            }
        }
    }

    private function getUrlFactory($path, $session)
    {
        $sessionType = ee()->config->item('cp_session_type');

        //just to prevent any errors
        if (!defined('BASE')) {
            $s = ($sessionType != 'c') ? $session->sdata['session_id'] : 0;
            define('BASE', constant('SELF') . '?S=' . $s . '&amp;D=cp');
        }

        $session_id = ($sessionType == 'cs') ? $session->sdata['fingerprint'] : $session->sdata['session_id'];
        $factory = new URL($path, $session_id);

        return $factory;
    }
}

/* End of file SessionsEnd.php */
/* Location: ./system/user/addons/Visitor/Hook/SessionsEnd.php */
