<?php
// 로그인 페이지
require_once 'config.php';

// 이미 로그인된 상태면 메인으로
if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error = '';

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // 입력값 검증
    if (empty($email) || empty($password)) {
        $error = "이메일과 비밀번호를 입력해주세요.";
    } else {
        // 사용자 정보 조회
        $query = "SELECT user_id, email, password, name FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            // 비밀번호 확인
            if (password_verify($password, $user['password'])) {
                // 로그인 성공 - 세션에 사용자 정보 저장
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['name'] = $user['name'];
                
                // 메인 페이지로 이동
                header("Location: index.php");
                exit();
            } else {
                $error = "이메일 또는 비밀번호가 일치하지 않습니다.";
            }
        } else {
            $error = "이메일 또는 비밀번호가 일치하지 않습니다.";
        }
        mysqli_stmt_close($stmt);
    }
}

// 헤더 렌더링
render_header('로그인 - ' . SITE_NAME, 'auth-page');
?>
<link rel="stylesheet" href="css/auth.css">

<div class="container">
    <div class="logo">
        <h1><?php echo SITE_NAME; ?></h1>
        <p>코기 보호자들의 특별한 공간</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo clean($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">이메일</label>
            <input type="email" id="email" name="email" placeholder="example@email.com" required>
        </div>
        
        <div class="form-group">
            <label for="password">비밀번호</label>
            <input type="password" id="password" name="password" placeholder="비밀번호 입력" required>
        </div>
        
        <button type="submit" class="btn">로그인</button>
    </form>
    
    <div class="links">
        계정이 없으신가요? <a href="register.php">회원가입</a>
    </div>
</div>

<?php render_footer(); ?>