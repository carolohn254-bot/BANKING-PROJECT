<?php
require_once __DIR__ . '/includes/auth.php';
do_logout(); session_start(); flash('You have been logged out.'); redirect('index.php');
