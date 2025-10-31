<?php
// 데이터베이스 연결 설정 파일
// 이 파일은 모든 PHP 페이지에서 사용됩니다

// 세션 시작 (로그인 상태 유지용)
session_start();

// 데이터베이스 접속 정보
define('DB_HOST', 'localhost');      // 서버 주소 (XAMPP는 localhost)
define('DB_USER', 'root');           // MySQL 사용자명 (기본: root)
define('DB_PASS', '');               // MySQL 비밀번호 (기본: 빈칸)
define('DB_NAME', 'corgitalk');      // 데이터베이스 이름

// MySQL 연결 (mysqli 사용)
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("데이터베이스 연결 실패: " . mysqli_connect_error());
}

// 한글 깨짐 방지 (UTF-8 설정)
mysqli_set_charset($conn, "utf8mb4");

// 사이트 기본 URL (나중에 사용)
define('BASE_URL', 'http://localhost/corgiisland/');

// 사이트 이름
define('SITE_NAME', '코기섬');

// 업로드 폴더 경로
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// 업로드 폴더가 없으면 자동 생성
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

// 유틸리티 함수들

// XSS 공격 방지 함수 (사용자 입력값 안전하게 출력)
function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// 로그인 확인 함수
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// 로그인 필수 페이지에서 사용
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

// 날짜 포맷 함수 (한국어 형식)
function format_date($date) {
    return date('Y년 m월 d일', strtotime($date));
}

// 시간 포맷 함수
function format_datetime($datetime) {
    return date('Y-m-d H:i', strtotime($datetime));
}

// SQL Injection 방지를 위한 이스케이프 함수
function escape_sql($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

// HTML 헤더 렌더링 함수
function render_header($title = '코기섬', $body_class = '', $additional_css = '') {
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo clean($title); ?></title>
        <link rel="stylesheet" href="css/common.css">
        <?php if ($additional_css): ?>
            <link rel="stylesheet" href="<?php echo $additional_css; ?>">
        <?php endif; ?>
    </head>
    <body<?php echo $body_class ? ' class="' . clean($body_class) . '"' : ''; ?>>
    <?php
}

// HTML 푸터 렌더링 함수
function render_footer() {
    ?>
        <!-- 푸터 -->
        <footer>
            <!-- Lottie 잔디 배경 -->
            <div id="grass-animation-footer"></div>
            
            <!-- Lottie 코기 애니메이션 -->
            <div id="corgi-animation-footer"></div>
            
            <div class="footer-content">
                <div class="footer-top">
                    <h3>코기섬 | 웰시코기 보호자 커뮤니티</h3>
                    <p>문의: wpqtqwqq8877@naver.com | Tel: 010-2547-1299</p>
                </div>
                
                <div class="footer-links">
                    <a href="#">개인정보처리방침</a>
                    <span>|</span>
                    <a href="#">이용약관</a>
                    <span>|</span>
                    <a href="#">고객센터</a>
                </div>
                
                <div class="footer-info">
                    <p>사업자등록번호: 123-45-67890 | 대표: 이이섬</p>
                    <p>주소: 혁신대로 443</p>
                </div>
                
                <div class="footer-copyright">
                    <p>&copy; 2024 코기섬(CorgiIsland). All rights reserved.</p>
                </div>
            </div>
        </footer>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
        <script>
        // 헤더 잔디 배경 애니메이션
        if (document.getElementById('grass-animation')) {
            lottie.loadAnimation({
                container: document.getElementById('grass-animation'),
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: 'lottie/Moving_Grass.json',
                rendererSettings: {
                    preserveAspectRatio: 'xMidYMid slice'
                }
            });
        }
        
        // 헤더 코기 애니메이션
        if (document.getElementById('corgi-animation')) {
            lottie.loadAnimation({
                container: document.getElementById('corgi-animation'),
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: 'lottie/Cute_Doggie.json'
            });
        }
        
        // 푸터 잔디 배경 애니메이션
        if (document.getElementById('grass-animation-footer')) {
            lottie.loadAnimation({
                container: document.getElementById('grass-animation-footer'),
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: 'lottie/Moving_Grass.json',
                rendererSettings: {
                    preserveAspectRatio: 'xMidYMid slice'
                }
            });
        }
        
        // 푸터 코기 애니메이션
        if (document.getElementById('corgi-animation-footer')) {
            lottie.loadAnimation({
                container: document.getElementById('corgi-animation-footer'),
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: 'lottie/Cute_Doggie.json'
            });
        }
        </script>
        <script src="corgiisland.js"></script>
    </body>
    </html>
    <?php
}

// 공통 헤더 (로그인 후 페이지용)
function render_common_header($user_name = '') {
    ?>
    <header>
        <!-- Lottie 잔디 배경 -->
        <div id="grass-animation"></div>
        
        <!-- Lottie 코기 애니메이션 -->
        <div id="corgi-animation"></div>

        <div class="header-content">
            <div class="logo">
                <a href="index.php" style="text-decoration: none;">
                    <img src="uploads/logo.png" alt="코기섬" class="logo-img">
                </a>
            </div>
            <?php if ($user_name): ?>
            <div class="user-info">
                <span><?php echo clean($user_name); ?>님 환영합니다</span>
                <a href="logout.php" class="btn-logout">로그아웃</a>
            </div>
            <?php endif; ?>
        </div>
    </header>
    <?php
}

// 공통 네비게이션
function render_navigation() {
    ?>
    <nav>
        <div class="nav-content">
            <a href="index.php">홈</a>
            <a href="butt.php">엉덩코기</a>
            <a href="board.php">코기talk</a>
            <a href="gallery.php">코기갤러리</a>
            <a href="test.php">코기테스트</a>
        </div>
        
        <!-- 모바일 햄버거 메뉴 -->
        <button class="hamburger-menu" id="hamburgerMenu" aria-label="메뉴 열기">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <!-- 모바일 메뉴 오버레이 -->
        <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
        
        <!-- 모바일 메뉴 -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-header">
                <h2><?php echo SITE_NAME; ?></h2>
                <button class="close-menu" id="closeMenu" aria-label="메뉴 닫기">&times;</button>
            </div>
            <div class="mobile-menu-links">
                <a href="index.php">🏠 홈</a>
                <a href="butt.php">🍑 엉덩코기</a>
                <a href="board.php">💬 코기talk</a>
                <a href="gallery.php">📷 코기갤러리</a>
                <a href="test.php">📝 코기테스트</a>
            </div>
        </div>
    </nav>
    <?php
}
?>