<?php

namespace Cockpit\Controller;

class Groups extends \Cockpit\AuthController {

   public function index() {

      if (!$this->module('cockpit')->hasaccess('cockpit', 'groups')) {
         return $this->helper('admin')->denyRequest();
      }

      $current = $this->user["_id"];
      $groups = $this->module('cockpit')->getGroups();

      return $this->render('groups:views/index.php', compact('current', 'groups'));
   }

   public function group($gid = null) {

      if (!$gid) {
         $gid = $this->group["_id"];
      }

      if (!$this->module('cockpit')->hasaccess('cockpit', 'groups')) {
         return $this->helper('admin')->denyRequest();
      }

      $group = $this->app->storage->findOne("cockpit/groups", ["_id" => $gid]);

      if (!$group) {
         return false;
      }

      $vars = $group['vars'];

      array_walk($vars, function ($value, $key) use (&$group) {
        unset($group['vars'][$key]);
        $key = \str_replace('__', '.', $key);
        $group['vars'][$key] = $value;
      });

      $fields = $this->app->retrieve('config/groups/fields', null);

      return $this->render('groups:views/group.php', compact('group', 'gid', 'fields'));
   }

   public function create() {

      $collections = $this->module('collections')->collections();

      // defaults for the creation of a new group
      $group = [
          'group' => '', // group name
          'password' => '',
          'vars' => [
              'finder.path' => '/storage',
              'finder.allowed_uploads' => 10,
              'assets.path' => '/storage/assets',
              'assets.allowed_uploads' => 10,
              'media.path' => '/storage/media'
          ],
          'admin' => false,
          'cockpit' => [
              'finder' => true,
              'rest' => true,
              'backend' => true
          ]
      ];

      return $this->render('groups:views/group.php', compact('group', 'collections'));
   }

   public function save() {

      if ($data = $this->param("group", false)) {

         $data["_modified"] = time();

         if (!isset($data['_id'])) {
            $data["_created"] = $data["_modified"];
         }

         $vars = $data['vars'];

         array_walk($vars, function ($value, $key) use (&$data) {
           unset($data['vars'][$key]);
           $key = \str_replace('.', '__', $key);
           $data['vars'][$key] = $value;
         });

         $this->app->storage->save("cockpit/groups", $data);

         return json_encode($data);
      }

      return false;
   }

   public function remove() {

      if ($data = $this->param("group", false)) {

         // can't delete own group
         if ($data["_id"] != $this->user["_id"]) {

            $this->app->storage->remove("cockpit/groups", ["_id" => $data["_id"]]);

            return '{"success":true}';
         }
      }

      return false;
   }

   public function find() {

      $options = $this->param('options', []);

      $groups = $this->storage->find("cockpit/groups", $options)->toArray(); // get groups from db
      $count = (!isset($options['skip']) && !isset($options['limit'])) ? count($groups) : $this->storage->count("cockpit/groups", isset($options['filter']) ? $options['filter'] : []);
      $pages = isset($options['limit']) ? ceil($count / $options['limit']) : 1;
      $page = 1;

      if ($pages > 1 && isset($options['skip'])) {
         $page = ceil($options['skip'] / $options['limit']) + 1;
      }

      return compact('groups', 'count', 'pages', 'page');
   }

}
