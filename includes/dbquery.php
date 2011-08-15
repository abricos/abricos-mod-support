<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Support
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

class SupportQuery {
	
	public static function MessageAppend(CMSDatabase $db, $msg, $pubkey){
		$contentid = CoreQuery::ContentAppend($db, $msg->bd, 'support');
		
		$sql = "
			INSERT INTO ".$db->prefix."spt_message (
				userid, title, pubkey, contentid, isprivate, status, dateline, upddate) VALUES (
				".bkint($msg->uid).",
				'".bkstr($msg->tl)."',
				'".bkstr($pubkey)."',
				".$contentid.",
				".bkint($msg->prt).",
				".SupportStatus::OPENED.",
				".TIMENOW.",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function MessageUpdate(CMSDatabase $db, $msg, $userid){
		$info = SupportQuery::Message($db, $msg->id, $userid, true);
		CoreQuery::ContentUpdate($db, $info['ctid'], $msg->bd);
		$sql = "
			UPDATE ".$db->prefix."spt_message
			SET
				title='".bkstr($msg->tl)."',
				upddate=".TIMENOW."
			WHERE messageid=".bkint($msg->id)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function MessageFields (CMSDatabase $db){
		return "
			m.messageid as id,
			m.userid as uid,
			m.title as tl,
			m.isprivate as prt,
			m.dateline as dl,
			m.status as st,
			m.statuserid as stuid,
			m.statdate as stdl,
			m.upddate as udl,
			m.cmtcount as cmt,
			m.cmtdate as cmtdl,
			m.cmtuserid as cmtuid 
		";
	}
	
	public static function Message(CMSDatabase $db, $messageid, $retarray = false){
		$sql = "
			SELECT
				".SupportQuery::MessageFields($db).",
				c.body as bd,
				c.contentid as ctid
			FROM ".$db->prefix."spt_message m
			INNER JOIN ".$db->prefix."content c ON m.contentid=c.contentid
			WHERE m.messageid=".bkint($messageid)." 
			LIMIT 1
		";
		return $retarray ? $db->query_first($sql) : $db->query_read($sql);
	}

	public static function MessageByContentId(CMSDatabase $db, $contentid, $retarray = false){
		$sql = "
			SELECT
				".SupportQuery::MessageFields($db).",
				c.body as bd,
				c.contentid as ctid
			FROM ".$db->prefix."spt_message m
			INNER JOIN ".$db->prefix."content c ON m.contentid=c.contentid
			WHERE m.contentid=".bkint($contentid)." 
			LIMIT 1
		";
		return $retarray ? $db->query_first($sql) : $db->query_read($sql);
	}
	
	public static function MessageList(CMSDatabase $db, $userid, $isModer, $lastupdate = 0){
		$lastupdate = bkint($lastupdate);
		$where = "WHERE m.upddate > ".$lastupdate." OR m.cmtdate > ".$lastupdate."";
		if (!$isModer){
			$where .= " AND (m.isprivate=0 OR (m.isprivate=1 AND m.userid=".bkint($userid)."))";
		}
		
		$sql = "
			SELECT
				".SupportQuery::MessageFields($db)."
				
			FROM ".$db->prefix."spt_message m
			".$where."
			ORDER BY m.upddate DESC
		";
		return $db->query_read($sql);
	}
	
	
	public static function Users(CMSDatabase $db, $uids){
		$ids = array();
		foreach ($uids as $uid => $v){
			array_push($ids, "u.userid=".bkint($uid));
		}
		
		$sql = "
			SELECT
				DISTINCT
				u.userid as id,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm,
				u.avatar as avt
			FROM ".$db->prefix."user u
			WHERE ".implode(" OR ", $ids)."
		";
		return $db->query_read($sql);
	}
	
	public static function MessageCommentInfoUpdate(CMSDatabase $db, $messageid){
		
		$sql = "
			SELECT
				(
					SELECT count(*) as cmt
					FROM ".$db->prefix."cmt_comment c
					WHERE c.contentid=m.contentid
					GROUP BY c.contentid
				) as cmt,
				(
					SELECT c4.dateedit as cmtdl
					FROM ".$db->prefix."cmt_comment c4
					WHERE m.contentid=c4.contentid
					ORDER BY c4.dateedit DESC
					LIMIT 1
				) as cmtdl,
				(
					SELECT c5.userid as cmtuid
					FROM ".$db->prefix."cmt_comment c5
					WHERE m.contentid=c5.contentid
					ORDER BY c5.dateedit DESC
					LIMIT 1
				) as cmtuid	
			FROM ".$db->prefix."spt_message m
			INNER JOIN ".$db->prefix."content c ON m.contentid=c.contentid
			WHERE m.messageid=".bkint($messageid)." 
			LIMIT 1
		";
		$row = $db->query_first($sql);
				
		$sql = "
			UPDATE ".$db->prefix."spt_message
			SET
				cmtcount=".bkint($row['cmt']).",
				cmtuserid=".bkint($row['cmtuid']).",
				cmtdate=".$row['cmtdl']."
			WHERE messageid=".bkint($messageid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	
	public static function CommentList(CMSDatabase $db, $userid, $isModer){
		$sql = "
			SELECT 
				a.commentid as id,
				a.parentcommentid as pid,
				t1.messageid as tkid,
				a.body as bd, 
				a.dateedit as de,
				a.status as st, 
				u.userid as uid, 
				u.username as unm,
				u.avatar as avt,
				u.firstname as fnm,
				u.lastname as lnm
			FROM ".$db->prefix."cmt_comment a 
			INNER JOIN (SELECT
					m.messageid, 
					m.contentid
				FROM ".$db->prefix."spt_message m 
				WHERE (m.isprivate=0 OR (m.isprivate=1 AND m.userid=".bkint($userid)."))
			) t1 ON t1.contentid=a.contentid
			LEFT JOIN ".$db->prefix."user u ON u.userid = a.userid
			ORDER BY a.commentid DESC  
			LIMIT 15
		";
		return $db->query_read($sql);
	}
	
	public static function ModeratorList(CMSDatabase $db){
		$sql = "
			SELECT 
				u.userid as id,
				u.username as unm,
				u.lastname as lnm,
				u.firstname as fnm,
				u.email as eml
			FROM ".$db->prefix."usergroup ug
			LEFT JOIN ".$db->prefix."group g ON g.groupid = ug.groupid
			LEFT JOIN ".$db->prefix."user u ON ug.userid = u.userid
			WHERE g.groupkey='".SupportGroup::MODERATOR."'
		";
		return $db->query_read($sql);
	}

	public static function MessageFiles(CMSDatabase $db, $messageid){
		$sql = "
			SELECT 
				bf.filehash as id,
				f.filename as nm,
				f.filesize as sz
			FROM ".$db->prefix."spt_file bf
			INNER JOIN ".$db->prefix."fm_file f ON bf.filehash=f.filehash
			WHERE bf.messageid=".bkint($messageid)."
		";
		return $db->query_read($sql);
	}
	
	public static function MessageFileAppend(CMSDatabase $db, $messageid, $filehash, $userid){
		$sql = "
			INSERT INTO ".$db->prefix."spt_file (messageid, filehash, userid) VALUES
			(
				".bkint($messageid).",
				'".bkstr($filehash)."',
				".bkint($userid)."
			)
		";
		$db->query_write($sql);
	}
	
	public static function MessageFileRemove(CMSDatabase $db, $messageid, $filehash){
		$sql = "
			DELETE FROM ".$db->prefix."spt_file
			WHERE messageid=".bkint($messageid)." AND filehash='".bkstr($filehash)."' 
		";
		$db->query_write($sql);
	}
	
	public static function MessageSetStatus(CMSDatabase $db, $messageid, $status, $userid){
		$sql = "
			UPDATE ".$db->prefix."spt_message
			SET
				status=".bkint($status).",
				statuserid=".bkint($userid).",
				statdate=".TIMENOW."
			WHERE messageid=".bkint($messageid)."
		";
		$db->query_write($sql);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

	

	
	public static function MyUserData(CMSDatabase $db, $userid, $retarray = false){
		$sql = "
			SELECT
				DISTINCT
				u.userid as id,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm,
				u.avatar as avt
			FROM ".$db->prefix."user u 
			WHERE u.userid=".bkint($userid)."
			LIMIT 1
		";
		return $retarray ? $db->query_first($sql) : $db->query_read($sql);
	}
	

	public static function MessageUnsetStatus(CMSDatabase $db, $messageid){
		$sql = "
			UPDATE ".$db->prefix."spt_message
			SET status=".SupportStatus::DRAW_OPEN.", statuserid=0, statdate=0
			WHERE messageid=".bkint($messageid)."
		";
		$db->query_write($sql);
	}
	
	/**
	 * Список участников проекта
	 * 
	 * @param CMSDatabase $db
	 * @param integer $messageid
	 */
	public static function MessageUserList(CMSDatabase $db, $messageid){
		$sql = "
			SELECT 
				p.userid as id,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm
			FROM ".$db->prefix."spt_userrole p
			INNER JOIN ".$db->prefix."user u ON p.userid=u.userid
			WHERE p.messageid=".bkint($messageid)."
		";
		return $db->query_read($sql);
	}
	
	/**
	 * Список участников проекта с расшириными полями для служебных целей (отправка уведомлений и т.п.)
	 * 
	 * @param CMSDatabase $db
	 * @param integer $messageid
	 */
	public static function MessageUserListForNotify(CMSDatabase $db, $messageid){
		$sql = "
			SELECT 
				p.userid as id,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm,
				u.email
				FROM ".$db->prefix."spt_userrole p
			INNER JOIN ".$db->prefix."user u ON p.userid=u.userid
			WHERE p.messageid=".bkint($messageid)."
		";
		return $db->query_read($sql);
	}
	
	
	

}

?>