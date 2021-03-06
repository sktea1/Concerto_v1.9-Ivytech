<?php
/**
 * This file was developed as part of the Concerto digital signage project
 * at RPI.
 *
 * Copyright (C) 2009 Rensselaer Polytechnic Institute
 * (Student Senate Web Technologies Group)
 *
 * This program is free software; you can redistribute it and/or modify it 
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.  You should have received a copy
 * of the GNU General Public License along with this program.
 *
 * @package      Concerto
 * @author       Web Technologies Group, $Author$
 * @copyright    Rensselaer Polytechnic Institute
 * @license      GPLv2, see www.gnu.org/licenses/gpl-2.0.html
 * @version      $Revision$
 */

/*Mike DiTore's CAS Login stuff
 *This allows CAS login functionality and is where all client interaction
 *takes place as far as login/logout/access control is concerned.
 *It should be included in every page that uses login.
 *
 *Nearing full functionality when used with the framework.
 *
 *last edited by mike, probably recently.
 */

//Get and setup the CAS client
include('CAS/CAS.php');
phpCAS::client(CAS_VERSION_2_0,CAS_URL,443,'/cas');
phpCAS::setDebug('/var/log/phpcas/phpcas.log');

//the following functions are designed for use as "requirements" 
//of site actions should return true, or perform some action 
//before returning; false indicates an error.
function check_login($callback)
{
   if(isLoggedIn())
      return true;

   if($callback->controller == 'users' && $callback->action == 'create')
      return true;

   //   if(phpCAS::isAuthenticated()) {
   //    login_login();
   //}

//   if(phpCAS::checkAuthentication())
      login_login();
}

function require_login()
{
/*Caching login: */
/*
   phpCAS::forceAuthentication();
   if(!isLoggedIn())
      login_login();
*/

/*Re-fetching user for each page that uses it: */
   if(!isLoggedIn())
    login_login();


   return true;
}

function require_action_auth($callback)
{
   check_login($callback);
   $target = $callback->controller;
   $id=$callback->currId;

   if(!has_action_auth($target, $id)) {
      $callback->flash("Sorry, you don't have permission to edit $target $id",'error');
      if($callback->action == $callback->defaultAction)
         redirect_to(ADMIN_URL);
      else
         redirect_to(ADMIN_URL.'/'.$callback->controller);
   }

   return true;
}

//these methods are interfaces to logon information.
function isLoggedIn()
{
   if(array_key_exists('user', $_SESSION) && 
    strlen($_SESSION['user']->username)>1) return true;
   return false;
}

function isAdmin()
{
   if(array_key_exists('user', $_SESSION) &&
    $_SESSION['user']->admin_privileges) return true;
   return false;
}

function firstName()
{
   return $_SESSION['user']->firstname;
}

function userName()
{  
   return $_SESSION['user']->username;
}

function has_action_auth($target, $id)
{
   if(!isLoggedIn()) return false;
   $grant=false;

   if($target=='screens') $target='screen';
   elseif($target=='feeds') $target='feed';
   elseif($target=='groups') $target='group';
   elseif($target=='users') $target='user';

   if($_SESSION['user']->can_write($target,$id)) {
      $grant=true;
   }

   return $grant;
}

//login/out functionality

function login_logout()
{
   $_SESSION = array();
   session_destroy();
   session_start();
   header("Cache-control: private"); // IE 6 Fix
}

function login_login($username = '', $password = '')
{
  if(isLoggedIn()) return true;
  if($username != ''){
    $test_usr = new User();
    if($test_usr->auth_test($username, $password)){
      $_SESSION['user'] = new user($username);
      $_SESSION['logged_un'] = $username;
      return true;
    } else {
      //$_SESSION['flash'][] = Array('error', "Unable to authenticate with the username/password combination");
      return false;
    }
  } else {
    redirect_to(ADMIN_URL.'/frontpage/login');
  }
}
