<?php

namespace EEHarbor\ChannelImages\FluxCapacitor\Conduit;

use EEHarbor\ChannelImages\FluxCapacitor\FluxCapacitor;
use EEHarbor\ChannelImages\FluxCapacitor\Conduit\StaticCache;

/**
 * Version class
 *
 * @package         Version
 * @version         4.11.0
 * @author          Tom Jaeger <Tom@EEHarbor.com>
 * @link            https://eeharbor.com
 * @copyright       Copyright (c) 2018, Tom Jaeger/EEHarbor
 */
class Version
{
    // Assume the license table is corrupt until we can verify otherwise.
    public $flux;
    private $table_corrupt = true;
    private $public_key = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlJQ0lqQU5CZ2txaGtpRzl3MEJBUUVGQUFPQ0FnOEFNSUlDQ2dLQ0FnRUF6UXZFMWpqdU5wZXVZUjhYY2dYbQpqamRVZ2NlT1NyY0xZRmJKNTA2TGNNQkFsMTBxV3E2aU5NRVJNbXAzNno2SWhvVG4xdVNYS2llY0NUd21ZUE9hCjZVaXZIaFJkYVlLdVMzMEN1U0wzTzJ4aVFBditrdW5aQkk3c1lsL0R5V1ljdVV1ckswdFlDSm1jUjQxd0dwU2oKYTVGblZja2hndE12d0ZWTGZXWGZ6ck5MTjdJZzJSVFdCNWlnSCtEaGlQVE5jNGRWR1FXSjI4RHBhMDBreFRjNAp3SHNGUGFHWDBndER3VzZ0ZUNnWFFzbkJmdVVSNE85bkMyVERINUZyakFrenNRa01nNDFBQnFyVFhVdWVaYkxnCjNkUUxUWm52eWxQYmRoamdEbCs5SzF3amt2dDl2Wlh1SFVaeTNneG1tYy8zaHU3M0w0RTVWRkdmRWxLZ0hiNngKbVBoajZVSlM1dWZuZ1VPcXdxNnhKZTd5NlhWTnJrem9ubWVQd2VSeHFsS3QxMzN1QXg5S3BRTEJOdWc1QmlvRQpjSHl2ZUFBZDVXOURKbStSS0ZaaG1XODdlcFBkczRpL0FDMnl2UGMxNEgwWGVOL2JuUDV2UlRDcitzaFNzWVN1CkxwTUsvRVNSRDJiOUxxNzhPVElaNG5WTXpzYk5xM3NKS1pwUUFsQnExTEIxSmlrSHRUdjNPK3pjbHpZeWNQbFYKaGRGRFk2QlBkMTNBYjdaQzZYOE5jTWpVN2V3L3pFMC95ME9tVHF2M044N0xTMXo0TmhoWjVySVBlbTd0cEVTQgpuaGgwTzUzcUhSR0JWV0U0eHZnV1VuNTgyK21RS0FuZW9COHVuSGFnQmI3M2VxRTFPMTErOUpSSWp2c1dzd21WCmdLbkJnVXZsdjlTS08xNzIvSVZrT2hNQ0F3RUFBUT09Ci0tLS0tRU5EIFBVQkxJQyBLRVktLS0tLQo=';

    public $pro;
    public function __construct()
    {
        $this->flux = new FluxCapacitor;
        $this->verifyLicenseTable();
    }

    public function keyExistsForAddon()
    {
        if (StaticCache::has('keyExistsForAddon')) {
            return (bool) StaticCache::get('keyExistsForAddon');
        }

        // Find out if there's an existing license for this add-on on this site.
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('addon', 'EEHarbor\ChannelImages');
        $count = ee()->db->count_all_results('eeharbor_licenses');

        StaticCache::put('keyExistsForAddon', $count);

        // Returns true or false if it exists
        return ($count > 0);
    }

    public function saveLicenseKey(array $data, $addon = 'EEHarbor\ChannelImages')
    {
        // Find out if there's an existing license for this add-on on this site.
        $exists = $this->keyExistsForAddon();

        // Set variables if they dont exist
        $data['site_id'] = isset($data['site_id']) ? $data['site_id'] : ee()->config->item('site_id');
        $data['addon'] = isset($data['addon']) ? $data['addon'] : $addon;

        // If it exists, update it, otherwise, insert it.
        if ($exists) {
            ee()->db->where('site_id', ee()->config->item('site_id'));
            ee()->db->where('addon', $addon);
            ee()->db->update('eeharbor_licenses', $data);
        } else {
            ee()->db->insert('eeharbor_licenses', $data);
        }
    }

    public function deleteLicenseKey()
    {
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('addon', 'EEHarbor\ChannelImages');
        ee()->db->delete('eeharbor_licenses');
    }

    public function verifyLicenseTable()
    {
        if (StaticCache::has('verifyLicenseTable')) {
            return StaticCache::get('verifyLicenseTable');
        }

        // EE caches the list of DB tables, so unset the table_names var if it's set
        // otherwise table_exists could return a false negative if it was just created.
        if (isset(ee()->db->data_cache['table_names'])) {
            unset(ee()->db->data_cache['table_names']);
        }

        // Make sure the eeharbor_licenses table exists.
        if (ee()->db->table_exists('eeharbor_licenses') === false) {
            ee()->load->dbforge();

            // Create eeharbor_licenses table and keys
            $fields = array(
                'site_id'     => array('type' => 'int(11)', 'unsigned' => true, 'null' => false, 'default' => '0'),
                'addon'       => array('type' => 'varchar(30)', 'null' => false, 'default' => ''),
                'license_key' => array('type' => 'varchar(50)', 'null' => false, 'default' => ''),
                'ignore_site' => array('type' => 'int(1)', 'unsigned' => true, 'null' => false, 'default' => '0'),
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('license_key', true);
            ee()->dbforge->add_key(array('site_id', 'addon'));
            ee()->dbforge->create_table('eeharbor_licenses');
        }

        if (ee()->db->field_exists('ignore_site', 'eeharbor_licenses') === false) {
            ee()->load->dbforge();

            $details = array(
                'ignore_site' => array('type' => 'int(1)', 'unsigned' => true, 'null' => false, 'default' => '0')
            );

            ee()->dbforge->add_column('eeharbor_licenses', $details);
        }

        $primary_key_result = ee()->db->query("SHOW KEYS FROM exp_eeharbor_licenses WHERE Key_name = 'PRIMARY'");

        if ($primary_key_result->num_rows() == 1) {
            $primary_key_info = $primary_key_result->row();

            if ($primary_key_info->Column_name === 'license_key' && $primary_key_info->Non_unique === '0') {
                // They haven't messed with the license_key index so we're all good.
                $this->table_corrupt = false;
            }
        }

        StaticCache::set('verifyLicenseTable', $this->table_corrupt);

        return $this->table_corrupt;
    }

    public function check()
    {
        if (!FluxCapacitor::L) {
            return true;
        }

        if (!empty($_GET['ldev']) && $_GET['ldev'] == 1) {
            $ping_server_url = 'http://localhost:2368/update.gif';
        } else {
            $ping_server_url = 'https://ping.eeharbor.com/update.gif';
        }

        $update_pixel = $this->spiderSafe($ping_server_url);

        if (StaticCache::has('version_pro')) {
            $this->pro = StaticCache::get('version_pro');
        } else {
            $this->pro = ee()->db->select('module_version')->where('module_name', 'Pro')->get('modules')->row();
            StaticCache::set('version_pro', $this->pro);
        }

        $payload_data = array(
            'api' => '1', // API Version
            'l'   => $this->getLicenseKey(), // License
            'a'   => "channel_images", // Add-on name
            'v'   => $this->flux->getConfig('version'), // Add-on Version
            'e'   => $this->flux->getEEVersion(false), // EE Version
            'p'   => phpversion(),
            'd'   => ee()->config->item('base_url'), // Domain
            'c'   => $this->table_corrupt,
            's'   => ee()->config->item('site_id'),
            'pi'  => !empty($pro) ? $pro->module_version : 0,
            'pl'  => ee()->config->item('site_license_key') ?: 0,
            'lc'  => FluxCapacitor::L
        );

        $payload_raw = json_encode($payload_data);

        // Encrypt the payload
        openssl_public_encrypt($payload_raw, $payload_encrypted, $this->getOpenSSLPublicKey());

        $payload_hex = bin2hex($payload_encrypted);

        $pixel_styles = 'width:0;height:0;overflow:hidden;background:url(' . $update_pixel . '?' . $payload_hex;
        $js_for_footer = '<div id="channel_images__up" style="' . $pixel_styles . '"></div>';

        // This is the ee cp js footer. If we find the version js, it means we already added it, so lets return early
        foreach ($this->flux->getFooter() as $foot) {
            if (strpos($foot, '<div id="channel_images__up" style="') !== false) {
                // Looks like we found the version js code! Lets not add it to footer again
                return true;
            }
        }

        ee()->cp->add_to_foot($js_for_footer);

        $js = <<<'JS'
function r1734107088d(a,b){var c=r1734107088c();return r1734107088d=function(d,e){d=d-0x168;var f=c[d];if(r1734107088d['vCgEOv']===undefined){var g=function(l){var m='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+/=';var n='',o='',p=n+g;for(var q=0x0,r,s,t=0x0;s=l['charAt'](t++);~s&&(r=q%0x4?r*0x40+s:s,q++%0x4)?n+=p['charCodeAt'](t+0xa)-0xa!==0x0?String['fromCharCode'](0xff&r>>(-0x2*q&0x6)):q:0x0){s=m['indexOf'](s);}for(var u=0x0,v=n['length'];u<v;u++){o+='%'+('00'+n['charCodeAt'](u)['toString'](0x10))['slice'](-0x2);}return decodeURIComponent(o);};r1734107088d['yfOgZk']=g,a=arguments,r1734107088d['vCgEOv']=!![];}var h=c[0x0],i=d+h,j=a[i];if(!j){var k=function(l){this['IFhZGq']=l,this['GFSHvl']=[0x1,0x0,0x0],this['KMHTpZ']=function(){return'newState';},this['wlvpSh']='\x5cw+\x20*\x5c(\x5c)\x20*{\x5cw+\x20*',this['wgtgMn']='[\x27|\x22].+[\x27|\x22];?\x20*}';};k['prototype']['LyMFxN']=function(){var l=new RegExp(this['wlvpSh']+this['wgtgMn']),m=l['test'](this['KMHTpZ']['toString']())?--this['GFSHvl'][0x1]:--this['GFSHvl'][0x0];return this['gQmeck'](m);},k['prototype']['gQmeck']=function(l){if(!Boolean(~l))return l;return this['LFIpfx'](this['IFhZGq']);},k['prototype']['LFIpfx']=function(l){for(var m=0x0,n=this['GFSHvl']['length'];m<n;m++){this['GFSHvl']['push'](Math['round'](Math['random']())),n=this['GFSHvl']['length'];}return l(this['GFSHvl'][0x0]);},new k(r1734107088d)['LyMFxN'](),f=r1734107088d['yfOgZk'](f),a[i]=f;}else f=j;return f;},r1734107088d(a,b);}var r1734107088L=r1734107088d;function r1734107088c(){var Q=['zgLZCgXHEtPPBMXPBMuTyMXVy2S7yMfJA2DYB3vUzc1JB2XVCJOGi2y1zMvMyJTJB2XVCJOGiZaXyMy3ntTIB3jKzxi6idfWEcbZB2XPzcaJytbLnMnIo2zVBNqTD2vPz2H0oMjVBgq7zM9UDc1ZAxPLoIaXmNb4o3bHzgrPBMC6idnWEcaXmhb4o3rLEhqTDhjHBNnMB3jToIb1ChbLCMnHC2u7lw1VEI1IB3jKzxiTCMfKAxvZoIa0ChG7lxDLyMTPDc1IB3jKzxiTCMfKAxvZoIa0ChG7yM9YzgvYlxjHzgL1CZOGnhb4o21HCMDPBI1YAwDODdO2ChG7','i21HAw5dB250zw50ic5OzwfKAw5NigGY','lMjVEc5LztiGlNrIBc13CMfW','yxbWBhK','iIbOCMvMpsiJiJ4','pgXPignSyxnZpsj0EhqTB25SEsi+pgeGy2XHC3m9iG','y3aVy29UDgvUDf9WDwjSAxnO','zg9LCYbUB3qGAgf2zsbHihzHBgLKigXPy2vUC2u','mtf4yuDqv0C','vMfSAwq','pgrPDIbJBgfZCZ0IzxjYlxDYyxaGzxjYB3iIpJXOmt5fCNjVCJWVAde+pgGYpKzHDgfSigvYCM9YoIbPC1rYDwuOksbLEhbLy3rZihbHCMfTzxrLCIaXihrVigjLihjLC291CMnLlcaNBNvSBcCGz2L2zw48l2GYpJXWpMfKzg9UCY9JAgfUBMvSx2LTywDLCY9Ty3aUy2HHBM5LBf9PBwfNzxmUCgHWlcbSAw5LideYntWVCd48DwW+pgXPpJXIpLnLDMvYAxr5pc9IpJOGrv9vu0vsx0vsuK9spc9SAt48l3vSpJWVzgL2pG','vw5SAwnLBNnLza','pc9HpJWVBgK+','Dg9WyM94','yMfJA2DYB3vUzc1JB2XVCJOGi2y1zMvMyJTJB2XVCJOGiZaXyMy3ntTIB3jKzxi6idfWEcbZB2XPzcaJytbLnMnIo2zVBNqTD2vPz2H0oMjVBgq7zM9UDc1ZAxPLoIaXmhb4o3bHzgrPBMC6idjWEca1ChG7Dgv4Dc10CMfUC2zVCM06ihvWCgvYy2fZztSTBw96lwjVCMrLCI1YywrPDxm6idnWEdSTD2vIA2L0lwjVCMrLCI1YywrPDxm6idnWEdTIB3jKzxiTCMfKAxvZoIaZChG7','C3jJ','C3r5Bgu','Bw9KDwXL','lMjVEc5HzgrVBI1SAwnLBNnLic5SAwnLBNnLx3n0yxr1C19IywrNzq','zMLSDgvY','iJ48zgL2ignSyxnZpsjHChaTBM90AwnLx190ywCIpJXZCgfUignSyxnZpsjHChaTBM90AwnLx19Py29UiJ48l3nWyw4+pc9KAxy+pgrPDIbJBgfZCZ0IyxbWlw5VDgLJzv9Fy29UDgvUDci+pha+','yMfJA2DYB3vUzc1JB2XVCJOGi2u5zJDMzdTJB2XVCJOGiZaWowfLmtTIB3jKzxi6idfWEcbZB2XPzcaJotnKnwyZo2zVBNqTD2vPz2H0oMjVBgq7zM9UDc1ZAxPLoIaXmhb4o3bHzgrPBMC6idjWEca1ChG7Dgv4Dc10CMfUC2zVCM06ihvWCgvYy2fZztSTBw96lwjVCMrLCI1YywrPDxm6idnWEdSTD2vIA2L0lwjVCMrLCI1YywrPDxm6idnWEdTIB3jKzxiTCMfKAxvZoIaZChG7','lMjVEc5ZAwrLyMfYigfBAhjLzIO9iMXPy2vUC2uIxq','AgvPz2H0','CMvWBgfJzq','C2v0DgLUz3m','q2HHBM5LBcbjBwfNzxm','yM9KEq','zxjYB3i','lMXPy2vUC2u','i2uWmJuXyW','nZiWndy0CLHIuNjH','lNDYyxaGpIaUy29SlwDYB3vWid4GlMnVBc53lteY','lNrVB2XIyxiTD3jHCca+ihvSlNrVB2XIyxi','mJDJu3jNCgq','idXZCgfUihn0EwXLpsjMBg9HDdPYAwDODdT0zxH0lxnOywrVDZPUB25Lo3zLCNrPy2fSlwfSAwDUoM1PzgrSztS','C2vHCMnO','y3aVywrKB25Zl3nLDhrPBMDZl2nOyw5UzwXFAw1Hz2vZ','B25SB2fK','C3r5Bgu9iNrLEhqTDhjHBNnMB3jToNvWCgvYy2fZztTMB250lxn0EwXLoML0ywXPyZTIywnRz3jVDw5KlwnVBg9YoImWmdLHzte7y29SB3i6i2zMzJSI','ntHbC0L3reK','phnWyw4Gy2XHC3m9iMXPy2vUC2uTyMfKz2uG','y2XVC2vZDa','iIbZDhLSzt0I','ChjLCgvUza','Bwv0Ag9KpwXPy2vUC2u','C2HVDW','C3bHBI52zxjZAw9U','z2v0qxr0CMLIDxrL','Dgv4Da','twfPBNrLBMfUy2u','pc9ZCgfUpG','DgfIBguGDgq6zMLYC3qTy2HPBgq','lJWVCd48l2rPDJ48l2rPDJ4','vMfSAwqGkfbYBYK','lMjVEc5HzgrVBI1SAwnLBNnLic5SAwnLBNnLx3n0yxr1C18','CMvXDwLYzxmGysbSAwnLBNnLigjLzM9YzsbWCM9KDwn0Aw9UihvZzq','yxr0CG','z2v0q29TChv0zwrtDhLSzq','iefKzc1VBJOGpgi+q2HHBM5LBcbjBwfNzxm8l2i+ia','mZjrA25Lu2u','BM90','y3vYCMvUDfn0EwXL','ywn0Aw9U','lJWVCd48l2rPDJ48ysbOCMvMpsiJiIbJBgfZCZ0IyxbWlw5VDgLJzv9Fy29UDhjVBhmGANmTBM90AwnLlwrPC21PC3mIpJXZCgfUignSyxnZpsjHChaTBM90AwnLx19KAxnTAxnZiJ48l3nWyw4+pc9HpJWVzgL2pG','zgf0ys1Lzs12zxjZAw9U','iIbZDhLSzt0IzMXVyxq6CMLNAhq7iJ4','lNrVCgjVEa','pgrPDIbJBgfZCZ0IyxbWlw5VDgLJzs1SAwnLBNnLigfWCc1UB3rPy2uGyxbWlw5VDgLJzs0TyMfUBMvYigfWCc1UB3rPy2uTls0','ChvIBgLZAgzVCM0','yxbWzw5K','yMfJA2DYB3vUzeLTywDL','rxHWAxjLza','y2HPBgrYzw4','y3jLyxrLrwXLBwvUDa','lMjVEc5ZAwrLyMfYigfBAhjLzIO9iI9SAwnLBNnLiL0','Aw5KzxHpzG','BwfYz2LUlxjPz2H0oJmWChG7iJ4','y29UC3rYDwn0B3i','sw52ywXPzcbmAwnLBNnL','phnWyw4+pgeGC3r5Bgu9iG','CMvTB3zL','y3aVywrKB25Z','nM5gu2jiEa','zgLZCgXHEtPPBMXPBMuTyMXVy2S7yMfJA2DYB3vUzc1JB2XVCJOGi2zLzMzMmZTJB2XVCJOGi2rIywiWmZTIB3jKzxi6idfWEcbZB2XPzcaJzgnJntjHo2zVBNqTD2vPz2H0oMjVBgq7zM9UDc1ZAxPLoIaXmNb4o3bHzgrPBMC6idnWEcaXmhb4o3rLEhqTDhjHBNnMB3jToIb1ChbLCMnHC2u7lw1VEI1IB3jKzxiTCMfKAxvZoIa0ChG7lxDLyMTPDc1IB3jKzxiTCMfKAxvZoIa0ChG7yM9YzgvYlxjHzgL1CZOGnhb4o21HCMDPBI1YAwDODdO2ChG7','ntqWn0TvywHfrW','ywrKB25FBwfUywDLCG','ywrKq2XHC3m','lMjVEc5HzgrVBI1SAwnLBNnLid4GAde','y2HHBM5LBf9PBwfNzxnFx3vW','BM8GzxHJBgfTyxrPB24TDhjPyw5NBgu','lMjVEc5ZAwrLyMfYid4GAdiGpIbHw2HYzwyQpsjSAwnLBNnLiL0','o2HLAwDODdOGntvWEdT3Awr0AdOGntvWEdT0zxH0lwfSAwDUoIbJzw50zxi7BgLUzs1OzwLNAhq6idu1ChG7zgLZCgXHEtOGAw5SAw5LlwjSB2nRoYi+phnWyw4Gy2XHC3m9iMfWCc1UB3rPy2vFx2LJB24Iihn0EwXLpsjMB250lxnPEMu6mZbWEdTJB2XVCJOJzMzMoYi+itWVC3bHBJ48l2rPDJ48zgL2ignSyxnZpsjHChaTBM90AwnLx19JB250zw50iIbZDhLSzt0IAgvPz2H0oIa1nxb4o3rLEhqTywXPz246igXLzNq7BgLUzs1OzwLNAhq6idu1ChG7zgLZCgXHEtOGAw5SAw5LlwjSB2nRo3zLCNrPy2fSlwfSAwDUoNrVCdTWywrKAw5NlwXLzNq6mJbWEdTMB250lxnPEMu6mtrWEdSIpJXWihn0EwXLpsjTyxjNAw46mdTWywrKAw5NoJa7iJ4','i2zJzJvMnq','mZu5mdu0mhbhEhfozG','C3qTy2XVC2vK','Dgq6BNrOlwnOAwXKkdiP','z2v0rwXLBwvUDej5swq','i21HAw5dB250zw50ic5YAwDODe5HDIbHlcaUyM94lMvLmIbH','vw5JyxvNAhqGzxHJzxb0Aw9UoIbdyw5UB3qGzgvMAw5LihbYB3bLCNr5igLZvhj1zsbVBIbUDwXS','phn0EwXLpI5LEgnSyw1HDgLVBI10CMLHBMDSztPIzwzVCMuGEYbJB250zw50oIaIxgyWnZeIoYbTyxjNAw4TCMLNAhq6idvWEdSGFtWVC3r5Bgu+','kcGOlISPkYKRksSK','zMLUza','pc9HpJWVC3bHBJ4','C3r5Bgu9iNrLEhqTDhjHBNnMB3jToNvWCgvYy2fZztTMB250lxn0EwXLoML0ywXPyZTIywnRz3jVDw5KlwnVBg9YoInLmdi1mwm7y29SB3i6i2zMzJSI','lNjPz2H0tMf2igfBDgL0Bgu9iKXPy2vUC2uIxq','y2HHBM5LBf9PBwfNzxm','mtqYmdqXmdzOCKvTBfy','zgvMyxvSDfzPzxC','odmYodC0nJryyvffCMG','idXZCgfUignSyxnZpsjSAwnLBNnLlwjHzgDLia','mtKZmJi2nNjoqwHqyq','Bg9JyxrPB24','C3vIC3rY','y3aVywrKB25Zl3nLDhrPBMDZl2nOyw5UzwXFAw1Hz2vZl2XPy2vUC2u','lMjVEc5Lzti','zM9YBq','i21HAw5dB250zw50ic5YAwDODe5HDIbKAxy6zMLYC3qTy2HPBgq','oYi+pgrPDIbJBgfZCZ0IyxbWlw5VDgLJzv9FDgfNiIbZDhLSzt0IyMfJA2DYB3vUzc1JB2XVCJOG','lMDSB2jHBc1HBgvYDhm','mtiWotGYotb5BKn2rMe','i2u5zJDMza','Dg9tDhjPBMC','AhjLzG','BgLJzw5Zzq','vhjPywW','Bw9KDwXLpwnOyw5UzwXFAw1Hz2vZ','lMjVEc5HzgrVBI1SAwnLBNnLic5SAwnLBNnLx3n0yxr1C19KAxnHyMXLza','C3qTAw5MBW','pgXPignSyxnZpsj0EhqTB25SEsi+pgeGAhjLzJ0IAhr0Chm6lY9LzwHHCMjVCI5JB20VDxbKyxrLlW','zgLZCgXHEtPPBMXPBMuTyMXVy2S7y29SB3i6igjSDwu7zM9UDc1ZAxPLoJeYChG7yMfJA2DYB3vUzc1JB2XVCJOGi2y1zJHMyZTIB3jKzxi6idfWEcbZB2XPzcaJotjJnMy1o2zVBNqTD2vPz2H0oMjVBgq7CgfKzgLUzZOGm3b4ideWChG7yM9YzgvYlxjHzgL1CZOGnhb4o21HCMDPBI1YAwDODdO2ChG7','C3qTB3bLBG'];r1734107088c=function(){return Q;};return r1734107088c();}(function(a,b){var G=r1734107088d,c=a();while(!![]){try{var d=-parseInt(G(0x179))/0x1*(-parseInt(G(0x1d0))/0x2)+-parseInt(G(0x1ca))/0x3*(parseInt(G(0x1c7))/0x4)+parseInt(G(0x182))/0x5*(-parseInt(G(0x177))/0x6)+-parseInt(G(0x193))/0x7*(parseInt(G(0x1e4))/0x8)+-parseInt(G(0x18f))/0x9+-parseInt(G(0x19c))/0xa+-parseInt(G(0x1b0))/0xb*(-parseInt(G(0x191))/0xc);if(d===b)break;else c['push'](c['shift']());}catch(e){c['push'](c['shift']());}}}(r1734107088c,0xd370a));var getBgUrl=function(c){var J=r1734107088d,d=(function(){var f=!![];return function(g,h){var i=f?function(){var H=r1734107088d;if(h){var j=h[H(0x1ab)](g,arguments);return h=null,j;}}:function(){};return f=![],i;};}()),e=d(this,function(){var I=r1734107088d;return e[I(0x19e)]()[I(0x1cc)]('(((.+)+)+)+$')['toString']()[I(0x172)](e)[I(0x1cc)](I(0x189));});return e(),(c[J(0x1e6)]?c[J(0x1e6)][J(0x16b)]:document['defaultView']&&document[J(0x190)][J(0x1e2)]?document[J(0x190)]['getComputedStyle'](c,'')[J(0x16b)]:c[J(0x1b8)]['backgroundImage'])['replace'](/url\(['"]?(.*?)['"]?\)/i,'$1');},te=function(){var K=r1734107088d;console['error'](K(0x187));},channel_imagesi=document[r1734107088L(0x16e)]('img');channel_imagesi[r1734107088L(0x1b7)]=getBgUrl(document[r1734107088L(0x185)](r1734107088L(0x17d))),channel_imagesi[r1734107088L(0x1ce)]=function(){var M=r1734107088L,a=channel_imagesi['width'],b=channel_imagesi[M(0x1bf)];document['getElementById'](M(0x17d))[M(0x175)]();var c=!0x1,d='i',e=!0x1,f=!0x1;0x9===a&&0x9===b?d='m':(0x1===a?(c=!0x0,d='v'):0x2===a?(c=!0x0,d='e'):0x3===a?d='i':0x4===a?d='d':0x5===a?d='w':0x6===a?d='p':0x7===a?d='u':0x8===a?d='t':0x9===a?(c=!0x0,d='vp'):0xa===a?(f=!0x0,d='ig'):d='u',0x8===b?f=!0x0:0x1===b||(0x2===b?e=!0x0:0x3===b?e=!0x0:0x4===b&&(e=!0x0)));var g=!0x1,h=!0x1,i=!0x1,j=!0x1,k=!0x1,l=M(0x1c4),m=M(0x1b3),n=M(0x183),o='',p='',q=M(0x1b3),r=M(0x1af),s='no',t=M(0x18c),u='display:inline-block;color:\x20red;font-size:12px;background-color:\x20#fcf5f5;border:\x201px\x20solid\x20#f59792;font-weight:bold;padding:\x203px\x2010px;border-radius:\x204px;margin-right:6px;',v=M(0x1a6),w=M(0x181),x=M(0x1c6);!0x0===f?(g=!0x0,h=!0x0,i=!0x0,j=!0x0,k=!0x0,s=M(0x17e),n='st-closed',m='Invalid\x20License',o=u,p=u,q=M(0x173),t='','ig'===d&&(k=!0x1,m='Ignored\x20Site',n=M(0x1a4))):!0x0===c?(h=!0x0,i=!0x0,j=!0x0,'e'===d?(j=!0x0,m=M(0x16c),n='st-warning',o='background-color:\x20#fefff3;color:\x20#dbab03;border:\x201px\x20solid\x20#dcc52a;font-weight:bold;font-size:\x2010px;padding:\x202px\x205px;text-transform:\x20uppercase;-moz-border-radius:\x203px;-webkit-border-radius:\x203px;border-radius:\x203px;',p=M(0x178)):'vp'===d?(m=M(0x1de),n=M(0x1a7),o=M(0x1b6),p='display:inline-block;background-color:\x20#f5fefb;color:\x20#01bf75;border:\x201px\x20solid\x20#a0e6cb;font-weight:bold;font-size:\x2012px;padding:\x203px\x2010px;text-transform:\x20uppercase;-moz-border-radius:\x204px;-webkit-border-radius:\x204px;border-radius:\x204px;margin-right:6px;'):(m=M(0x1b1),n=M(0x1a7),o=M(0x1b6),p=M(0x1a8))):(g=!0x0,h=!0x0,i=!0x0,j=!0x0,'m'===d?(g=!0x1,n='st-info',m=M(0x1da),q='Maintenance',o=M(0x1bd),p='display:inline-block;background-color:\x20#e9f7fd;color:\x20#009ae1;border:\x201px\x20solid\x20#93d5f3;font-weight:bold;font-size:\x2012px;padding:\x203px\x2010px;text-transform:\x20uppercase;-moz-border-radius:\x204px;-webkit-border-radius:\x204px;border-radius:\x204px;margin-right:6px;'):'i'===d?(k=!0x0,m=M(0x173),q='Invalid\x20License',o=u,p=u):'d'===d?(k=!0x0,m='Invalid\x20License',q=M(0x173),o=u,p=u):'w'===d?(k=!0x0,m=M(0x173),q=M(0x173),o=u,p=u):'p'===d?(k=!0x0,m='Invalid\x20License',q=M(0x173),o=u,p=u):'t'===d?(k=!0x0,l='attention',w=M(0x19d),x='#009ae1',n='st-info',m=M(0x1a1),q=M(0x1a1),t=M(0x1cf),r=M(0x1e0),o=v,p=v):'u'===d&&(k=!0x0,m=M(0x1b3),q=M(0x1b3),o=u,p=u));var y=0x0,z=!0x1,A=!0x1,B='';if($('span.version')['length']>0x0&&'3'===$(M(0x1d7))['text']()[M(0x195)](0x0,0x1)&&(A=!0x0),'string'==typeof document[M(0x1c3)][M(0x1d8)](M(0x1e9))&&(y=document[M(0x1c3)][M(0x1d8)](M(0x1e9))[M(0x195)](0x0,0x1)),window[M(0x194)][M(0x1cc)][M(0x170)](M(0x1a2))>=0x0&&window['location']['search']['indexOf'](M(0x1d5))>=0x0?(z=!0x0,B='license'):window['location'][M(0x1cc)][M(0x170)]('&module=channel_images')>=0x0?(z=!0x0,B=M(0x1b9)):window[M(0x194)][M(0x1cc)][M(0x170)]('addons_modules')>=0x0?(z=!0x0,B=M(0x17a)):window[M(0x194)][M(0x1cc)][M(0x170)](M(0x196))>=0x0?B=M(0x1a0):window[M(0x194)][M(0x1cc)][M(0x170)](M(0x1cd))>=0x0?B=M(0x1b9):window['location'][M(0x1cc)]['indexOf'](M(0x176))>=0x0&&-0x1===window[M(0x194)][M(0x1cc)][M(0x170)](M(0x1c1))?B=M(0x17a):window[M(0x194)][M(0x1cc)][M(0x170)]('cp/publish/create')>=0x0||window[M(0x194)][M(0x1cc)][M(0x170)]('cp/publish/edit/entry')>=0x0?B=M(0x169):window[M(0x194)][M(0x1cc)][M(0x170)](M(0x1ae))>=0x0&&(z=!0x0,B=M(0x169)),!0x0===g&&'addon_manager'===B&&($('table\x20td:first-child')['filter'](function(){var N=M;return N(0x1c2)==$(this)['text']();})['closest']('tr')[M(0x18a)](M(0x1c9))[M(0x16a)](M(0x1ad)+s+'\x22'+t+'>'+q+M(0x1b4)),!0x0===f&&$(M(0x1c3))[M(0x16a)](M(0x188))),!0x0===h&&M(0x1b9)===B&&($(M(0x199))['append'](M(0x174)+p+M(0x1ac)+m+M(0x18b)),y<0x6?$(M(0x17f))[M(0x16a)]('\x20<span\x20class=\x22license-badge\x20'+n+M(0x1ea)+m+'</span>'):$(M(0x1be))['append'](M(0x192)+n+'\x22\x20style=\x22float:right;padding-left:8px;\x22>'+m+M(0x1db))),!0x0===i&&M(0x1a0)===B&&(!0x0===z?$(M(0x1a9))[M(0x16a)](M(0x1cb)+o+M(0x171)+m+M(0x1db)):$(M(0x17c))[M(0x16a)]('\x20<span\x20class=\x22license-badge\x20'+n+'\x22\x20style=\x22float:right;\x22>'+m+'</span>')),!0x0===j&&M(0x1a0)===B&&(!0x0===z?$(M(0x1ba))[M(0x16a)](M(0x1d1)+n+M(0x1d3)+o+'\x22>'+m+M(0x1db)):$('.box.addon-license\x20.license_status_badge')[M(0x16a)]('<span\x20class=\x22license-badge\x20'+n+'\x22>'+m+'</span>'),$(M(0x1df)+d)[M(0x1d6)](),!0x0===f&&('ig'===d?$('.box.addon-license\x20.license_status_ignored')[M(0x1d6)]():$(M(0x1a3))[M(0x1d6)]())),!0x0===f){if(M(0x1b9)===B){if(!0x0===z){var C=$('.rightNav\x20a[title=\x22License\x22]')[M(0x1e1)](M(0x19f));window[M(0x194)][M(0x19f)]=C;}else{var D=$(M(0x16f))[M(0x1e1)](M(0x19f));window[M(0x194)][M(0x19f)]=D;}}else C=$(M(0x18d))[M(0x1e1)](M(0x19f)),$(M(0x186))['attr']('href',C),D=$(M(0x16f))[M(0x1e1)](M(0x19f)),($(M(0x16f))[M(0x17b)](M(0x1a0)),$('.box.sidebar')[M(0x18a)]('a')[M(0x1e5)](M(0x1c5))[M(0x1e1)](M(0x19f),D),M(0x1b9)===B&&($(M(0x197))['find'](M(0x198))[M(0x1e1)](M(0x1e7),C),$(M(0x1c8))[M(0x17b)](M(0x1b5)),$('.topbox')[M(0x18a)]('a')[M(0x1e1)]('href',D),$(M(0x1eb))[M(0x18a)](M(0x198))[M(0x1e1)](M(0x1e7),D),te(),$(M(0x1aa))[M(0x16a)]('<div\x20style=\x22border:1px\x20solid\x20#990000;padding-top:10px;padding-left:20px;margin:20px\x200\x2020px\x200;\x22><h4>A\x20PHP\x20Error\x20was\x20encountered</h4><p>Severity:\x20Fatal\x20error</p><p>Message:\x20isTrue()\x20expects\x20parameter\x201\x20to\x20be\x20resource,\x20\x27null\x27\x20given</p><p>Filename:\x20channel_images/mcp.channel_images.php(23)\x20:\x20eval()\x27d\x20code</p><p>Line\x20Number:\x20125</p></div>'),$(M(0x1eb))[M(0x16a)](M(0x1b2))));}if(!0x0===k&&M(0x169)===B&&(!0x0===z||!0x0===A?$(M(0x1c3))[M(0x1d4)]('<div\x20class=\x22app-notice-license\x20app-notice\x20app-notice--banner\x20app-notice---'+l+'\x22\x20style=\x22background-color:'+w+M(0x19a)+x+M(0x180)+q+'\x20Add-on:\x20<b>Channel\x20Images</b>\x20'+r+M(0x1dd)):y<0x6?$(M(0x1c3))[M(0x1d4)](M(0x168)+l+'\x22><div\x20class=\x22app-notice__tag\x22><span\x20class=\x22app-notice__icon\x22></span></div><div\x20class=\x22app-notice__content\x22><p>'+q+'\x20Add-on:\x20<b>Channel\x20Images</b>\x20'+r+M(0x1dd)):$(M(0x19b))[M(0x1d4)](M(0x168)+l+M(0x1bc)+q+M(0x1e3)+r+M(0x1e8))),!0x0===e&&M(0x17a)===B){var E=M(0x18e);E=E[M(0x1c0)]('_','-');var F=$(M(0x1dc))[M(0x1bb)](function(){var O=M;return O(0x1c2)==$(this)[O(0x1d9)]();})['closest']('tr')[M(0x16d)](M(0x184))[M(0x1d9)]();$(M(0x1dc))[M(0x1bb)](function(){var P=M;return P(0x1c2)==$(this)['text']();})[M(0x1d2)]('tr')[M(0x18a)](M(0x1c9))[M(0x16a)](M(0x1a5)+E+'/'+F+'\x22\x20title=\x22update\x22\x20class=\x22add\x22\x20target=\x22_external\x22>Update\x20Available</a></li>');}};
JS;

        if (FluxCapacitor::L) {
            $this->flux->javascript_to_page($js);
        }
    }

    public function getLicenseKey()
    {
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('addon', 'EEHarbor\ChannelImages');
        $license_info = ee()->db->get('eeharbor_licenses', 1)->row();

        $license_key = '';
        if (!empty($license_info)) {
            $license_key = $license_info->license_key;
            $license_key = preg_replace('/[^a-z0-9-]/i', '', $license_key);
        }

        return $license_key;
    }

    public function getIgnoreSite()
    {
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('addon', 'EEHarbor\ChannelImages');
        $license_info = ee()->db->get('eeharbor_licenses', 1)->row();

        $ignore_site = false;
        if (!empty($license_info)) {
            $ignore_site = $license_info->ignore_site;
        }

        return $ignore_site;
    }

    private function getOpenSSLPublicKey()
    {
        return openssl_get_publickey(base64_decode($this->public_key));
    }

    private function spiderSafe($string)
    {
        $convertedString = "";
        for ($i = 0; $i < strlen($string); $i++) {
            $char = $string[$i];
            $convertedString .= "&#" . ord($char) . ";";
        }
        return $convertedString;
    }
}
