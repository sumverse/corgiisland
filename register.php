<?php
// 회원가입 페이지
require_once 'config.php';

// 이미 로그인된 상태면 메인으로
if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $name = trim($_POST['name']);
    $nickname = trim($_POST['nickname']);
    
    // 입력값 검증
    if (empty($email) || empty($password) || empty($name) || empty($nickname)) {
        $error = "모든 항목을 입력해주세요.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "올바른 이메일 형식이 아닙니다.";
    } elseif ($password !== $password_confirm) {
        $error = "비밀번호가 일치하지 않습니다.";
    } elseif (strlen($password) < 6) {
        $error = "비밀번호는 최소 6자 이상이어야 합니다.";
    } else {
        // 이메일 중복 체크
        $check_query = "SELECT user_id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "이미 사용 중인 이메일입니다.";
        } else {
            // 닉네임 중복 체크
            $check_nickname = "SELECT user_id FROM users WHERE nickname = ?";
            $stmt2 = mysqli_prepare($conn, $check_nickname);
            mysqli_stmt_bind_param($stmt2, "s", $nickname);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_store_result($stmt2);
            
            if (mysqli_stmt_num_rows($stmt2) > 0) {
                $error = "이미 사용 중인 닉네임입니다.";
            } else {
                // 비밀번호 암호화
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // 회원 정보 저장
                $insert_query = "INSERT INTO users (email, password, name, nickname) VALUES (?, ?, ?, ?)";
                $stmt3 = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt3, "ssss", $email, $hashed_password, $name, $nickname);
                
                if (mysqli_stmt_execute($stmt3)) {
                    $success = "회원가입이 완료되었습니다! 로그인해주세요.";
                } else {
                    $error = "회원가입 중 오류가 발생했습니다.";
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// 헤더 렌더링
render_header('회원가입 - ' . SITE_NAME, 'auth-page');
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
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo clean($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">이름 *</label>
            <input type="text" id="name" name="name" placeholder="실명 (예: 홍길동)" required>
        </div>
        
        <div class="form-group">
            <label for="nickname">닉네임 *</label>
            <input type="text" id="nickname" name="nickname" placeholder="게시판에 표시될 닉네임 (예: 코기맘)" required>
        </div>
        
        <div class="form-group">
            <label for="email">이메일 *</label>
            <input type="email" id="email" name="email" placeholder="example@email.com" required>
        </div>
        
        <div class="form-group">
            <label for="password">비밀번호 *</label>
            <input type="password" id="password" name="password" placeholder="최소 6자 이상" required>
        </div>
        
        <div class="form-group">
            <label for="password_confirm">비밀번호 확인 *</label>
            <input type="password" id="password_confirm" name="password_confirm" placeholder="비밀번호 재입력" required>
        </div>
        
        <button type="submit" class="btn">가입하기</button>
    </form>
    
    <div class="links">
        이미 계정이 있으신가요? <a href="login.php">로그인</a>
    </div>
</div>

<?php render_footer(); ?>