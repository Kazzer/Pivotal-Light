<?php
session_start();
if (strcmp($_SERVER['HTTPS'], "on") != 0) {
  header('Location: https://kadeem.com/plight/index.php');
}
require('controller.php');
if (isset($_POST['logout'])) {
  unset($_SESSION['guid']);
}
?>
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-GB">
  <head>
    <link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css" />
    <style>
      body {
        margin: 0.625em;
      }
      td.rejected {
        background-color: #FF0000;
      }
      tr.started {
        background-color: #00FF00;
      }
      tr.bug {
        font-weight: bold;
      }
    </style>
    <title>Pivotal Light</title>
  </head>
  <body>
    <h1>Pivotal Light</h1>
    <script src="http://code.jquery.com/jquery-latest.js" />
    <script src="js/bootstrap.min.js" />
<?php
date_default_timezone_set('America/Toronto');
if (!isset($_SESSION['guid'])) {
  if (!isset($_POST['uname']) || !isset($_POST['pword'])) {
?>
    <form action="/plight/index.php" method="post" enctype="application/x-www-form-urlencoded">
      <label>Username: <input name="uname" type="email" placeholder="Email address" required="required" /></label>
      <label>Password: <input name="pword" type="password" required="required" /></label>
      <input type="submit" name="submit" value="submit" />
    </form>
<?php
  }
  else {
    $_SESSION['guid'] = getGuid();
  }
}
if (isset($_SESSION['guid'])) {
?>
    <form action="/plight/index.php" method="post" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="logout" value="true" />
      <input type="submit" name="submit" value="Logout" />
    </form>
    <form action="/plight/index.php" method="get" enctype="application/x-www-form-urlencoded">
      <label>Select project: 
        <select name="project_id" required="required" onchange="javascript:this.form.submit();">
<?php
  getProjects();
  if (isset($_GET['project_id'])) {
    getStories($_GET['project_id']);
  }
}
?>
  </body>
</html>

