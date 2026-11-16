<?php

/*
 *                      CMS RuDi v1.10.9
 *                    https://cmsrudi.ru/
 *
 *              Written by CMS RuDi Team, 2026
 *           produced by DagIT, (https://dagit.su)
 *
 *                  LICENSED BY GNU/GPL v2
 */

namespace components\users\backend;

class model extends \cms_model_users {

    public function getUser($user_id) {
        if (is_string($user_id)) {
            return parent::getUser($user_id);
        }
        return $this->db->getItemById('users', $user_id);
    }

    public function getCountUsers() {
        return $this->db->getCount('users');
    }

    public function getUsers($is_online = null) {
        if ($is_online !== null) {
            return parent::getUsers($is_online);
        }
        return $this->db->selectOnly('i.id')
                        ->select('i.login')
                        ->select('i.nickname')
                        ->select('i.rating')
                        ->select('i.email')
                        ->select('i.regdate')
                        ->select('i.logdate')
                        ->select('i.last_ip')
                        ->select('i.is_locked')
                        ->select('i.is_deleted')
                        ->select('p.id', 'group_id')
                        ->select('p.title', 'group_title')
                        ->joinLeft('user_groups', 'p', 'p.id=i.group_id')
                        ->get('users');
    }

    public function userExist($login, $email) {
        return $this->db->resetFilters()
                        ->selectOnly('login')
                        ->select('email')
                        ->filterStart()
                        ->filterEqual('login', $login)
                        ->filterOr()
                        ->filterEqual('email', $email)
                        ->filterEnd()
                        ->filterEqual('is_deleted', 0)
                        ->getItem('users');
    }

    public function createUser($data) {
        return $this->db->insert('users', $data);
    }

    public function createProfile($data) {
        return $this->db->insert('user_profiles', $data);
    }

    public function updateUser($user_id, $data) {
        return $this->db->update('users', $user_id, $data);
    }

    public function getGroup($group_id) {
        return $this->db->getItemById('user_groups', $group_id);
    }

    public function getGroupsIdTitle() {
        return $this->db->selectOnly('id')
                        ->select('title')
                        ->orderBy('id', 'ASC')
                        ->get('user_groups');
    }

    public function getCountUserGroups() {
        return $this->db->getCount('user_groups');
    }

    public function getUserGroups() {
        return $this->db->get('user_groups');
    }

    public function createGroup($data) {
        return $this->db->insert('user_groups', $data);
    }

    public function updateGroup($group_id, $data) {
        return $this->db->update('user_groups', $group_id, $data);
    }

    public function getUserGroupsAccess() {
        return $this->db->orderBy('access_type', 'ASC')->get('user_groups_access');
    }

    public function getCountBanlist() {
        return $this->db->getCount('banlist');
    }

    public function getBanlist() {
        return $this->db->select('u.nickname', 'user_nickname')
                        ->joinLeft('users', 'u', 'u.id=i.user_id')
                        ->get('banlist');
    }

    public function createBan($data) {
        return $this->db->insert('banlist', $data);
    }

    public function updateBan($ban_id, $data) {
        return $this->db->update('banlist', $ban_id, $data);
    }

    public function activateUser($user_id) {
        $this->db->update('users', $user_id, ['is_locked' => 0]);
        $this->db->filterEqual('user_id', $user_id)->deleteFiltered('users_activate');
    }
}