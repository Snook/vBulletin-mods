<?php
    // ########################################################################
    //
    // gXboxLive, Copyright © 2006, Ghryphen (github.com/ghryphen)
    //
    // If you have fixes, improvements or other additions to make to
    // gXboxLive, please contact me at ghryphen@gmail.com for collaboration.
    // I appreciate your kind consideration.
    //
    // This work is licensed under the Creative Commons
    // Attribution-Noncommercial-No Derivative Works 3.0 United States License.
    // To view a copy of this license, visit
    // http://creativecommons.org/licenses/by-nc-nd/3.0/us/ or send a letter to
    // Creative Commons, 171 Second Street, Suite 300,
    // San Francisco, California, 94105, USA.
    //
    // ########################### SVN info ###################################
    // $Id: functions_gxboxlive.php 1083 2008-11-19 16:03:30Z ghryphen $
    // $Rev: 1083 $
    // $LastChangedBy: ghryphen $
    // $Date: 2008-11-19 08:03:30 -0800 (Wed, 19 Nov 2008) $
    error_reporting(E_ALL & ~E_NOTICE);
    if (!is_object($vbulletin->db))
    {
        exit;
    }

if (!$vbulletin->db->query_first("SHOW COLUMNS FROM " . TABLE_PREFIX . "userfield LIKE 'field" . $vbulletin->options['gxbl_user_field'] . "'"))
{
    $vbulletin->options['gxbl_user_field'] = false;
}

if ($vbulletin->options['gxbl_user_field'])
{
    $gxblprod = $vbulletin->db->query_first("SELECT * FROM `" . TABLE_PREFIX . "product` WHERE productid = 'gxboxlive'");

    function gxbl_has_access()
    {
        global $vbulletin;
    
        if ($vbulletin->options['gxbl_usergroup_access'] == '' || $vbulletin->options['gxbl_usergroup_access'] == '0' || is_member_of($vbulletin->userinfo, explode(',',$vbulletin->options['gxbl_usergroup_access'])))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    function gxbl_bannedgroups()
    {
        global $vbulletin;
    
        $bannedgroups = array();
        $bannedgroups["0"] = "Undefined";
        foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
        {
            if (!($usergroup['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
            {
                $bannedgroups["$usergroupid"] = $usergroup['title'];
            }
        }
        
        return implode(',', array_keys($bannedgroups));
    }
    
    function gxbl_displaygroups()
    {
        global $vbulletin;
    
        $displaygroups = '';    
        if($vbulletin->options['gxbl_displaygroups'])
        {
            $displaygroups = 'AND (';
    
            foreach (explode(",", $vbulletin->options['gxbl_displaygroups']) AS $usergroupid)
            {
                $displaygroups .= " FIND_IN_SET('$usergroupid', membergroupids) OR usergroupid = '$usergroupid' OR";
            }
    
            return substr($displaygroups, 0, -2) . " )";
    
        }
        else
        {
            return null;
        }
    }
    
    function gxbl_counts()
    {
        global $vbulletin, $db;
    
        $main_query = "SELECT
            COUNT(user.userid) AS `usercount`,
            SUM(gxboxlive.score) AS `score`,
            SUM(gxboxlive.reputation) AS `reputation`,
            SUM(gxboxlive.gold) AS `gold`
        FROM
            `" . TABLE_PREFIX . "user` AS user
        LEFT JOIN
            `" . TABLE_PREFIX . "userfield` AS userfield ON (user.userid = userfield.userid)
        LEFT JOIN
            `" . TABLE_PREFIX . "gxboxlive` AS gxboxlive ON (userfield.userid = gxboxlive.userid)
        WHERE
            user.usergroupid NOT IN(" . gxbl_bannedgroups() . ")
            " . gxbl_displaygroups() . "
        AND
            field" . $vbulletin->options['gxbl_user_field'] . " != ''
        AND
              user.posts >= '" . $vbulletin->options['gxbl_required_posts'] . "'
        AND
            user.lastvisit >= '" . iif($vbulletin->options['gxbl_user_timeout'] == '0', '0', (TIMENOW - ($vbulletin->options['gxbl_user_timeout'] * 86400)) ) . "'
        AND
            gxboxlive.strikes = '0'
        AND
            gxboxlive.score >= '" . iif($vbulletin->options['gxbl_show_unranked'] == '0', '1', '0') . "'
        ";
    
        $counts = array();
    
        if ($vbulletin->options['gxbl_show_unranked'] == '0')
        {
            $gxbl_show_unranked = "AND gxboxlive.score != '0'";
        }
    
        $res = $db->query_first($main_query);
    
        $counts['usercount'] = $res['usercount'];
        $counts['score'] = vb_number_format($res['score']);
        $counts['reputation'] = vb_number_format($res['reputation']);
        $counts['silver'] = vb_number_format($res['usercount'] - $res['gold']);
        $counts['gold'] = vb_number_format($res['gold']);
    
        $res = $db->query_first("
            SELECT
                COUNT(DISTINCT title) AS `gamecount`
            FROM
                `" . TABLE_PREFIX . "gxboxlive_games` AS gxboxlive_games
            LEFT JOIN
                `" . TABLE_PREFIX . "gxboxlive` AS gxboxlive ON (gxboxlive_games.userid = gxboxlive.userid)
            WHERE
                strikes = '0'
                " . $gxbl_show_unranked . "
        ");
    
        $counts['games'] = vb_number_format($res['gamecount']);
    
        return $counts;
    }
    
    function gxbl_topstats()
    {
        global $vbulletin, $db, $vbphrase, $stylevar;
    
        $main_query = "SELECT
            user.userid,
            user.username,
            user.posts,
            user.lastvisit,
            user.usergroupid,
            user.membergroupids,
            userfield.field" . $vbulletin->options['gxbl_user_field'] . ",
            gxboxlive.*,
            COUNT(*) as count
        FROM
            `" . TABLE_PREFIX . "user` AS user
        LEFT JOIN
            `" . TABLE_PREFIX . "userfield` AS userfield ON (user.userid = userfield.userid)
        LEFT JOIN
            `" . TABLE_PREFIX . "gxboxlive` AS gxboxlive ON (userfield.userid = gxboxlive.userid)
        WHERE
            user.usergroupid NOT IN(" . gxbl_bannedgroups() . ")
            " . gxbl_displaygroups() . "
        AND
            field" . $vbulletin->options['gxbl_user_field'] . " != ''
        AND
              user.posts >= '" . $vbulletin->options['gxbl_required_posts'] . "'
        AND
            user.lastvisit >= '" . iif($vbulletin->options['gxbl_user_timeout'] == '0', '0', (TIMENOW - ($vbulletin->options['gxbl_user_timeout'] * 86400)) ) . "'
        AND
            gxboxlive.strikes = '0'
        AND
            gxboxlive.score >= '" . iif($vbulletin->options['gxbl_show_unranked'] == '0', '1', '0') . "'
        ";
    
        $gxbl = array();
    
        $top_limit = $vbulletin->options['gxbl_show_stats'];
    
        //top avatars
        $filtered_avatars = array('http://tiles.xbox.com/tiles/8y/ov/0Wdsb2JhbC9EClZWVEoAGAFdL3RpbGUvMC8yMDAwMAAAAAAAAAD+ACrT.jpg','/xweb/lib/images/QuestionMark64x64.jpg');
    
        $res = $db->query_read($main_query ."
            AND
                gxboxlive.avatar NOT IN ('" . implode("','", array_values($filtered_avatars)) . "')
            GROUP BY
                gxboxlive.avatar
            ORDER BY
                count DESC,
                gxboxlive.score DESC
            LIMIT " . $top_limit);
    
        while ($avatar = $db->fetch_array ($res))
        {
            eval('$gxbl[avatars] .= "' . fetch_template('gxbl_top_avatars') . '";');
        }
    
        //top players
        $res = $db->query_read($main_query . "
            GROUP BY
                user.userid
            ORDER BY
                gxboxlive.score DESC,
                gxboxlive.gamertag ASC
            LIMIT " . $top_limit);
    
        while ($player = $db->fetch_array ($res))
        {
            $player['url_gamertag'] = urlencode($player['gamertag']);
    
            eval('$gxbl[players] .= "' . fetch_template('gxbl_top_players') . '";');
        }
    
        //top zones
        $gxbl[zones] = "";
        /*
        $res = $db->query_read($main_query . "
            GROUP BY
                gxboxlive.zone
            ORDER BY
                count DESC
            LIMIT " . $top_limit);
    
        while ($zone = $db->fetch_array ($res))
        {
            eval('$gxbl[zones] .= "' . fetch_template('gxbl_top_zones') . '";');
        }
        */
    
        //top games
        $res = $db->query_read("
            SELECT
                *,
                count(*) AS count
            FROM
                `" . TABLE_PREFIX . "gxboxlive_games` AS gxboxlive_games
            LEFT JOIN
                `" . TABLE_PREFIX . "gxboxlive` AS gxboxlive ON (gxboxlive_games.userid = gxboxlive.userid)
            WHERE
                gxboxlive.strikes = '0'
            AND
                gxboxlive.score >= '" . iif($vbulletin->options['gxbl_show_unranked'] == '0', '1', '0') . "'
            GROUP BY
                title
            ORDER BY
                count DESC,
                position ASC,
                title ASC
            LIMIT " . $top_limit);
    
        while ($game = $db->fetch_array ($res))
        {
            $game['link']    =    preg_replace("/&amp;compareTo=(.*)/i", '', $game['link']);
    
            eval('$gxbl[games] .= "' . fetch_template('gxbl_top_games') . '";');
        }
    
        return $gxbl;
    }
    
    function gxbl_suffix($num)
    {
        if(strlen($num)>=2 && substr($num, (strlen($num)-2), 1)==1)
        {
            $suff = "th";
        }
        else if(substr($num, (strlen($num)-1), 1)==1)
        {
            $suff = "st";
        }
        else if(substr($num, (strlen($num)-1), 1)==2)
        {
            $suff = "nd";
        }
        else if(substr($num, (strlen($num)-1), 1)==3)
        {
            $suff = "rd";
        }
        else if(substr($num, (strlen($num)-1), 1)>>2 && substr($num, (strlen($num)-1), 1)<=9 || substr($num, (strlen($num)-1), 1)==0)
        {
            $suff = "th";
        }
    
        $suffixedNum = $num.$suff;
    
        return $suff;
    
    }
    
    function gxbl_updateranks()
    {
        global $vbulletin;
    
        $vbulletin->db->query_write("ALTER TABLE `" . TABLE_PREFIX . "gxboxlive` ORDER BY score DESC");
    
        $main_query = "SELECT
            user.userid,
            user.username,
            user.posts,
            user.lastvisit,
            user.usergroupid,
            user.membergroupids,
            userfield.field" . $vbulletin->options['gxbl_user_field'] . ",
            gxboxlive.*
        FROM
            `" . TABLE_PREFIX . "user` AS user
        LEFT JOIN
            `" . TABLE_PREFIX . "userfield` AS userfield ON (user.userid = userfield.userid)
        LEFT JOIN
            `" . TABLE_PREFIX . "gxboxlive` AS gxboxlive ON (userfield.userid = gxboxlive.userid)
        WHERE
            user.usergroupid NOT IN(" . gxbl_bannedgroups() . ")
            " . gxbl_displaygroups() . "
        AND
            field" . $vbulletin->options['gxbl_user_field'] . " != ''
        AND
              user.posts >= '" . $vbulletin->options['gxbl_required_posts'] . "'
        AND
            user.lastvisit >= '" . iif($vbulletin->options['gxbl_user_timeout'] == '0', '0', (TIMENOW - ($vbulletin->options['gxbl_user_timeout'] * 86400)) ) . "'
        ";
    
        $main_query .= "
        AND
            gxboxlive.strikes = '0'
        AND
            gxboxlive.score != '0'
        ";
    
        $rank_pos = 1;
    
        $res = $vbulletin->db->query_read($main_query ."
            ORDER BY
                gxboxlive.score DESC,
                gxboxlive.gamertag ASC
            ");
    
        while ($rankuser = $vbulletin->db->fetch_array($res))
        {
            if($rankuser['score'] == '0')
            {
                $position = NULL;
            }
            else
            {
                $position = $rank_pos++;
            }
    
            $vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "gxboxlive SET `rank` = '" . $position . "', `error` = '0' WHERE `userid` = '" . $rankuser['userid'] . "'");
        }
    
    }
    
    function gxbl_fieldid( $userfield )
    {
        if($userfield == 0)
        {
            $fieldid = false;
        }
        else if (is_numeric( $userfield ))
        {
            $fieldid = $userfield;
        }
        else
        {
            $fieldid = str_replace( "field", "", $userfield );
        }
    
        return $fieldid;
    }
    
    function gxbl_parsegamercard($string, $user)
    {
        global $vbulletin;
        
        $player['existing'] = $user;
    
      //preg_match_all('#<div class="XbcgcContainer.*?"><div class="Xbcgc"><h3 class="XbcGamertag(.*?)">.*?<div class="XbcgcInfo"><a href="http://live.xbox.com/member/(.*?)"><img class="XbcgcGamertile" height="64" width="64" src="(.*?)" alt=".*?" /></a><div class="XbcgcStats"><p><span class="XbcFLAL">Rep</span><span class="XbcFRAR"><img src="/xweb/lib/images/gc_repstars_external_(.*?).gif" /></span></p><p><span class="XbcFLAL"><img alt="Gamerscore" src="/xweb/lib/images/G_Icon_External.gif" /></span><span class="XbcFRAR">(.*?)</span></p><p><span class="XbcFLAL">Zone</span><span class="XbcFRAR">(.*?)</span></p></div></div><div class="XbcgcGames">(.*?)</div></div></div>#si', $string, $matches, PREG_SET_ORDER);
        preg_match_all('#<div class="XbcGamercard">\s*?<div class="Header">\s*?<div class="Gamertag">\s*?<a href=".*?">\s*?<span class="(.*)">(.*)</span>\s*?</a>\s*?</div>\s*?</div>\s*?<div class="Body">\s*?<a href=".*?">\s*?<img class="GamerPic" width="64" height="64" src="(.*)" alt=".*?" title=".*?"/>\s*?</a>\s*?<div class="Stats">\s*?<div style="height:30px; line-height:30px;">\s*?<div class="Stat">Rep</div>\s*?(.*?)\s*?</div>\s*?<div style="height:30px; line-height:30px;">\s*?<div class="Stat">\s*?<div class="GSIcon"></div>\s*?<div class="Stat">(.*?)</div>\s*?</div>\s*?</div>\s*?</div>\s*?</div>\s*?<div class="Footer">(.*)</div>\s*?</div>#si', $string, $matches, PREG_SET_ORDER);

        /*
            1 = Silver/Gold
            2 = Gamertag
            3 = Gamertile
            4 = Reputation
            5 = Score
            6 = Zone
            7 = Games array
        */
    
        if (trim($matches[0][2]) != '' && $matches[0][6]) // if gamertag and zone
        {
            $player['userid'] = $player['existing']['vbuserid'];
            $player['gamertag'] = urldecode($matches[0][2]);
            $player['avatar'] = $matches[0][3];
            $player['reputation'] = $matches[0][4];
            $player['score'] = $matches[0][5];
            //$player['zone'] = $matches[0][6];
            $player['zone'] = 'unknown';
    
            if($matches[0][1] == 'Gold')
            {
                $player['gold'] = 1;
            }
            else
            {
                $player['gold'] = 0;
            }
    
            // Process games
            if ($matches[0][6]) {
              //preg_match_all('#<a href="(.*?)"><img height="32" width="32" title="(.*?)" alt="" src="(.*?)" /></a>#si', $matches[0][7], $lastplayed, PREG_SET_ORDER);
                preg_match_all('#<a href="(.*?)">\s*?<img class="Game" width="32" height="32" src="(.*?)" alt=".*?" title="(.*?)" />\s*?</a>#si', $matches[0][6], $lastplayed, PREG_SET_ORDER);

                $player['games'] = array();
    
                if (is_array($lastplayed))
                {
                    foreach ($lastplayed as $key => $item) {
    
                        $player['games'][$key]['title'] = $item[3];
                        $player['games'][$key]['link'] = $item[1];
                        $player['games'][$key]['image'] = $item[2];
    
                    }
    
                }
    
                $player['lastplayed'] = $vbulletin->db->escape_string(serialize($player['games']));
            }
            else
            {
                $player['games'] = false;
                $player['lastplayed'] = false;
            }
    
            return $player;
        }
        else
        {
            return false;
        }
    }
    
    function gxbl_deleteuserid( $userid )
    {
        global $vbulletin;
    
        $affected_rows = 0;
    
        $vbulletin->db->query_write("
            DELETE FROM " . TABLE_PREFIX . "gxboxlive
            WHERE userid = '" . $userid . "'
        ");
    
        $affected_rows = $affected_rows + $vbulletin->db->affected_rows();
    
        $vbulletin->db->query_write("
            DELETE FROM " . TABLE_PREFIX . "gxboxlive_games
            WHERE userid = '" . $userid . "'
        ");
    
        $affected_rows = $affected_rows + $vbulletin->db->affected_rows();
    
        if($affected_rows > 0)
        {
            gxbl_updateranks();
        }
    
        return $affected_rows;
    }
    
    function gxbl_gettid($string)
    {
        $tid = parse_url($string);
        $tid = explode('&', html_entity_decode($tid['query']));
        $tid = explode('=', $tid['0']);
    
        return $tid['1'];
    }
    
    function gxbl_updategamer($player)
    {
        global $vbulletin;
    
        $affected_rows = 0;
    
        if ($player['avatar'] == '/xweb/lib/images/QuestionMark64x64.jpg' && $player['score'] == '0')
        {
            $playerexists = false;
        }
        else
        {
            $playerexists = true;
        }
        
        if($player['existing']['score'] == NULL)
        {
            $newuser = true;
        }
        else
        {
            $newuser = false;
        }
    
        if($player['score'] != $player['existing']['score'])
        {
            $scoreupdated = 1;
        }
        else
        {
            $scoreupdated = 0;
        }
    
        $setquery = "
            userid = '" . $player['userid'] . "',
            gold = '" . $player['gold'] . "',
            gamertag = '" . $player['gamertag'] . "',
            avatar = '" . $vbulletin->db->escape_string($player['avatar']) . "',
            score = '" . $player['score'] . "',
            reputation = '" . $player['reputation'] . "',
            zone = '" . $player['zone'] . "',
            lastplayed = '" . $player['lastplayed'] . "',
            updated = '" . TIMENOW . "'
        ";
        
        
        if ($playerexists)
        {    
            if ($newuser)
            {
                $vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "gxboxlive SET
                    " . $setquery . ",
                    strikes = '0',
                    error = '" . $scoreupdated . "',
                    firstseen = '" . TIMENOW . "'
                ");
            }
            else
            {
                $vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "gxboxlive SET
                    " . $setquery . ",
                    strikes = '0',
                    error = '" . $scoreupdated . "'
                    WHERE userid = '" . $player['userid'] . "'
                ");
            }
    
            $affected_rows = $affected_rows + $vbulletin->db->affected_rows();
    
            if($player['games'])
            {
                foreach($player['games'] as $key => $game)
                {
                    $game['tid'] = gxbl_gettid($game['link']);
    
                    $vbulletin->db->query_write("REPLACE INTO " . TABLE_PREFIX . "gxboxlive_games SET
                        userid = '" . $player['userid'] . "',
                        position = '" . $key . "',
                        tid = '" . $vbulletin->db->escape_string($game['tid']) . "',
                        title = '" . $vbulletin->db->escape_string($game['title']) . "',
                        link = '" . $vbulletin->db->escape_string($game['link']) . "',
                        image = '" . $vbulletin->db->escape_string($game['image']) . "',
                        updated = '" . TIMENOW . "'
                    ");
    
                    $vbulletin->db->query_write("REPLACE INTO " . TABLE_PREFIX . "gxboxlive_gameslist SET
                        tid = '" . $vbulletin->db->escape_string($game['tid']) . "',
                        title = '" . $vbulletin->db->escape_string($game['title']) . "',
                        image = '" . $vbulletin->db->escape_string($game['image']) . "'
                    ");
                }
            }
        }
        else
        {
            // Give them 10 strikes just in case something wacky is going on
            if($player['existing']['strikes'] < 10)
            {
                $strikes = $player['existing']['strikes'] + 1;
    
                $vbulletin->db->query_write("REPLACE INTO " . TABLE_PREFIX . "gxboxlive SET
                    " . $setquery . ",
                    strikes = '" . $strikes . "'
                ");
    
                $affected_rows = $affected_rows + $vbulletin->db->affected_rows();
    
            }
            else
            {
                gxbl_deleteuserid( $player['userid'] );
    
                $vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "userfield SET
                    field" . $vbulletin->options['gxbl_user_field'] . " = ''
                    WHERE userid = '" . $player['userid'] . "'
                ");
    
                $affected_rows = $affected_rows + $vbulletin->db->affected_rows();
            }
    
        }

        return $affected_rows;
    }

}
?>