<?php
/**
 * JComments - Joomla Comment System
 *
 * Enable avatar support for JComments
 *
 * @version 1.0
 * @package JComments
 * @author docenttmp, original author Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2014 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

class plgJCommentsAvatar extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}

	public function onPrepareAvatar(&$comment)
	{
		$comments = array();
		$comments[0] =& $comment;
		$this->onPrepareAvatars($comments);
	}

	public function onPrepareAvatars(&$comments)
	{
		$db = JFactory::getDBO();

		$avatar_type = $this->params->get('avatar_type', 'zygo');
		$avatar_default_avatar = $this->params->get('avatar_default_avatar');
		$avatar_custom_default_avatar = $this->params->get('avatar_custom_default_avatar');

		$avatar_link = $this->params->get('avatar_link', 0);
		$avatar_link_target = $this->params->get('avatar_link_target');
		$avatar_link_target = $avatar_link_target != '_self' ? ' target="' . $avatar_link_target . '"' : '';

		$users = array();
		foreach ($comments as &$comment) {
			if ($comment->userid != 0) {
				$users[] = (int)$comment->userid;
			}

			$comment->avatar = '';
			$comment->online = '';
		}

		$users = array_unique($users);

		$avatars = array();

		switch ($avatar_type) {
			
			case 'zygo':

    if (!count($users)) break;

    if(!file_exists(JPATH_ROOT."/plugins/user/zygo_profile/zygo_helper.php")){
     echo "Вы не установили плагин 'Zygo profile'";
     break;
    }
    include_once (JPATH_ROOT."/plugins/user/zygo_profile/zygo_helper.php");
    if (empty(ZygoHelper::$profile)){
     echo "Создайте дополнительные поля в плагине расширенного профиля 'Zygo profile'";
     break;
    }
    $fid = false;
    foreach(ZygoHelper::$profile as $f=>$prof){
     if($prof['fieldType'] == 'avatar'){
      $fid = $f;
      break;
     }
    }
    if(!$fid){
     echo "Создайте поле для типа 'Аватар' в плагине расширенного профиля 'Zygo profile'";
     break;     
    }
    $db->setQuery('SELECT user_id, profile_value as avatar, userid as online FROM `#__user_profiles` '.
	 'LEFT JOIN `#__session` ON user_id=userid WHERE user_id in (' . implode(',', $users) . ') AND profile_key = '.$db->quote("zygo_profile.".$fid));
    $avatars = $db->loadObjectList('user_id');

    foreach ($comments as &$comment) {

     $uid = (int)$comment->userid;

     if (isset($avatars[$uid]) && !empty(trim($avatars[$uid]->avatar))) {
      $comment->avatar = JURI::base() . $avatars[$uid]->avatar;
	  $comment->online = $avatars[$uid]->online;	  
     }
    }	
    break;
				
		}

		if ($avatar_default_avatar == 'custom' && empty($avatar_custom_default_avatar)) {
			$avatar_default_avatar = 'default';
		}

		foreach ($comments as &$comment) {
			if (empty($comment->avatar)) {
				switch ($avatar_default_avatar) {
					case 'custom':
						$comment->avatar = JURI::base() . ltrim($avatar_custom_default_avatar, '/');
						break;

					case 'default':
						$comment->avatar = JURI::base() . 'components/com_jcomments/images/no_avatar.png';
						break;
				}
			}

			$comment->avatar = self::createImg($comment->avatar, JComments::getCommentAuthorName($comment));

			if ($avatar_link && !empty($comment->profileLink)) {
				$comment->avatar = self::createLink($comment->avatar, $comment->profileLink, $avatar_link_target);
			}
		}

		return;
	}

	protected static function getItemid($link)
	{
		$menu = JFactory::getApplication()->getMenu();
		$item = $menu->getItems('link', $link, true);

		$id = null;

		if (is_array($item)) {
			if (count($item) > 0) {
				$id = $item[0]->id;		
			}
		} else if (is_object($item)) {
			$id = $item->id;
		}

		return ($id !== null) ? '&Itemid=' . $id : '';
	}
	
	protected static function createImg($src, $alt = '')
	{
		return '<img src="' . $src . '" alt="' . htmlspecialchars($alt) . '" />';
	}

	protected static function createLink($text, $link, $target = '')
	{
		return !empty($link) ? ('<a href="' . $link . '"' . $target . '>' . $text . '</a>') : $text;
	}
}
