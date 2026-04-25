<?php
// public_html/logout.php
// Just forwards to the action handler
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

redirect('actions/logout.php');