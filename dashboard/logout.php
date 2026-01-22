<?php
session_start();
session_unset();
session_destroy();
header("Location: applicant_login");
exit;
