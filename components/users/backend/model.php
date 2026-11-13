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

class model extends \cms\model {

    public function getCountUsers() {
        return $this->db->getCount('users');
    }

    public function getUsers() {
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
}