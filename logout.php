<?php
// 로그아웃 처리
require_once 'config.php';

// 세션 초기화 (로그인 정보 삭제)
session_unset();
session_destroy();

// 로그인 페이지로 이동
header("Location: login.php");
exit();
?>