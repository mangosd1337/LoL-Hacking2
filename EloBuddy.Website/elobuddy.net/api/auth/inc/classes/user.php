<?php


class user{
    private $ipbLocation;
    public $displayName, $memberID, $groupName, $groupID, $avatarPath;

    public function __construct($ipdLocation){
        $this->ipbLocation = $ipdLocation;
    }


    public function authenticate($username, $password){
        $member = \IPS\Member::load($username, 'name', NULL);

        if (!$member->member_id){
          return false;
        }

        if ( \IPS\Login::compareHashes( $member->members_pass_hash, $member->encryptedPassword( $password ) ) ){

          $groupID = $member->member_group_id;
          $groupName = \IPS\Db::i()->select(["word_custom"], "core_sys_lang_words", ['word_key=?', "core_group_{$groupID}"])->first();
          $displayName = $member->real_name;

          $this->groupName = $groupName;
          $this->displayName = $displayName;
          $this->groupID = $groupID;
          $this->avatarPath = $member->pp_main_photo;

          return true;
        }


        return false;
    }


    public function getAvatar(){
        if(emptY($this->avatarPath)){
          return null;
        }
        

        $file = $this->ipbLocation . '/uploads/' . $this->avatarPath;

        return (file_exists($file)) ? $this->readFile($file) : null;
    }


    // Helper function
    public function readFile($file){
        $handle = fopen($file, "rb");
        $bytes = fread($handle, filesize($file));
        fclose($handle);

        return $bytes;
    }
}
