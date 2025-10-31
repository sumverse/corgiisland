<?php
// 메인 페이지
require_once 'config.php';
require_login();

// 엉덩코기 상위 3개 조회
$butt_query = "SELECT b.*, c.corgi_name, u.nickname as user_nickname 
               FROM butt_corgi b 
               JOIN corgis c ON b.corgi_id = c.corgi_id 
               JOIN users u ON b.user_id = u.user_id 
               ORDER BY b.likes_count DESC, b.created_at DESC 
               LIMIT 3";
$butt_result = mysqli_query($conn, $butt_query);

// 최신 게시글 3개 조회 (닉네임 표시)
$board_query = "SELECT p.*, u.nickname as user_nickname, c.corgi_name
                FROM size_posts p 
                JOIN users u ON p.user_id = u.user_id 
                LEFT JOIN corgis c ON p.corgi_id = c.corgi_id 
                ORDER BY p.created_at DESC LIMIT 3";
$board_result = mysqli_query($conn, $board_query);

// 갤러리 3개 조회
$gallery_query = "SELECT g.*, c.corgi_name, u.nickname as user_nickname 
                  FROM mvp_gallery g 
                  JOIN corgis c ON g.corgi_id = c.corgi_id 
                  JOIN users u ON g.user_id = u.user_id 
                  ORDER BY g.created_at DESC LIMIT 3";
$gallery_result = mysqli_query($conn, $gallery_query);

$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : '사용자';

render_header(SITE_NAME . ' - 메인', '', 'css/index.css');
render_common_header($user_name);
render_navigation();
?>

<div class="main-section">
    <!-- 엉덩코기 이번주 MVP -->
    <div class="section-title">
        <span>엉덩코기 이번주 MVP</span>
        <a href="butt.php">더보기 →</a>
    </div>
    
    <div class="gallery-grid">
        <?php if (mysqli_num_rows($butt_result) > 0): ?>
            <?php 
            $rank = 1;
            while ($item = mysqli_fetch_assoc($butt_result)): 
            ?>
                <a href="butt.php" style="text-decoration: none; color: inherit;">
                    <div class="gallery-card butt-mvp-card" data-rank="<?php echo $rank; ?>">
                        <div class="gallery-card-image">
                            <div class="mvp-rank-badge rank-<?php echo $rank; ?>">
                                <?php 
                                if ($rank == 1) echo '🥇 1등';
                                else if ($rank == 2) echo '🥈 2등';
                                else if ($rank == 3) echo '🥉 3등';
                                ?>
                            </div>
                            <?php if ($item["photo_path"]): ?>
                                <img src="<?php echo clean($item['photo_path']); ?>" alt="코기 사진">
                            <?php else: ?>
                                이미지
                            <?php endif; ?>
                        </div>
                        <div class="gallery-card-info">
                            <div class="gallery-card-title"><?php echo clean($item['corgi_name']); ?></div>
                            <div class="gallery-card-meta">
                                <span><?php echo clean($item['user_nickname']); ?></span>
                                <span>❤ <?php echo intval($item['likes_count']); ?></span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php 
            $rank++;
            endwhile; 
            ?>
        <?php else: ?>
            <div class="empty-state">
                <p>등록된 엉덩코기가 없습니다.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- 상단: 코기talk 최신글 3개 (목록형) -->
    <div class="section-title">
        <span>코기talk 최신글</span>
        <a href="board.php">더보기 →</a>
    </div>
    
    <div class="board-section">
        <?php if (mysqli_num_rows($board_result) > 0): ?>
            <div class="board-list">
                <?php while ($post = mysqli_fetch_assoc($board_result)): ?>
                    <a href="board.php?id=<?php echo intval($post['post_id']); ?>" style="text-decoration: none; color: inherit;">
                        <div class="board-item-card">
                            <?php if (!empty($post["image_path"])): ?>
                                <div class="board-item-image">
                                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                                         alt="게시글 이미지"
                                         onerror="this.parentElement.innerHTML='이미지를<br>불러올 수<br>없습니다'">
                                </div>
                            <?php endif; ?>
                            
                            <div class="board-item-content">
                                <span class="board-category"><?php echo clean($post['product_name']); ?></span>
                                <h3 class="board-title"><?php echo clean($post['title']); ?></h3>
                                <p class="board-content">
                                    <?php 
                                    $content_preview = $post['content'];
                                    $content_preview = str_replace("\xEF\xBB\xBF", '', $content_preview);
                                    $content_preview = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content_preview);
                                    $content_preview = preg_replace('/\s+/', ' ', trim($content_preview));
                                    
                                    if (empty($content_preview)) {
                                        echo '(내용 없음)';
                                    } else {
                                        $preview = mb_substr($content_preview, 0, 100, "UTF-8"); 
                                        echo clean($preview);
                                        if (mb_strlen($content_preview, "UTF-8") > 100) echo "...";
                                    }
                                    ?>
                                </p>
                                
                                <div class="board-meta">
                                    <span><?php echo clean($post['user_nickname']); ?></span>
                                    <?php if ($post['corgi_name']): ?>
                                        <span>코기: <?php echo clean($post['corgi_name']); ?></span>
                                    <?php endif; ?>
                                    <span><?php echo format_datetime($post['created_at']); ?></span>
                                    <span>조회 <?php echo intval($post['views']); ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>등록된 게시글이 없습니다.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- 중간: 코기갤러리 최신글 3개 -->
    <div class="section-title">
        <span>코기갤러리 최신글</span>
        <a href="gallery.php">더보기 →</a>
    </div>
    
    <div class="gallery-grid">
        <?php if (mysqli_num_rows($gallery_result) > 0): ?>
            <?php while ($item = mysqli_fetch_assoc($gallery_result)): ?>
                <a href="gallery.php" style="text-decoration: none; color: inherit;">
                    <div class="gallery-card">
                        <div class="gallery-card-image">
                            <?php if ($item["photo_path"]): ?>
                                <img src="<?php echo clean($item['photo_path']); ?>" alt="코기 사진">
                            <?php else: ?>
                                이미지
                            <?php endif; ?>
                        </div>
                        <div class="gallery-card-info">
                            <div class="gallery-card-title"><?php echo clean($item['corgi_name']); ?></div>
                            <div class="gallery-card-meta">
                                <span><?php echo clean($item['user_nickname']); ?></span>
                                <span>❤ <?php echo intval($item['likes_count']); ?></span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>등록된 사진이 없습니다.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 하단: 테스트 이미지 슬라이더 -->
<div class="test-slider-section">
    <div class="main-section">
        <div class="section-title">
            <span>코기테스트</span>
            <a href="test.php">전체보기 →</a>
        </div>
    </div>
    
    <div class="test-image-slider">
        <button class="slider-btn prev-btn" onclick="moveSlide(-1)">&#10094;</button>
        
        <div class="slider-wrapper">
            <div class="slider-track">
                <!-- 애착유형 테스트 -->
                <div class="slide-item">
                    <a href="test.php?type=love">
                        <img src="uploads/test1.jpg" alt="애착 유형 테스트">
                        <div class="slide-overlay">
                            <h3>애착 유형 테스트</h3>
                            <p>우리 코기와의 애착 관계를 알아보세요</p>
                        </div>
                    </a>
                </div>
                
                <!-- MBTI 테스트 -->
                <div class="slide-item">
                    <a href="test.php?type=mbti">
                        <img src="uploads/test2.jpg" alt="코기 MBTI 테스트">
                        <div class="slide-overlay">
                            <h3>코기 MBTI 테스트</h3>
                            <p>우리 코기의 성격 유형을 알아보세요</p>
                        </div>
                    </a>
                </div>
                
                <!-- 보호자 유형 테스트 -->
                <div class="slide-item">
                    <a href="test.php?type=owner">
                        <img src="uploads/test3.jpg" alt="나는 어떤 보호자?">
                        <div class="slide-overlay">
                            <h3>나는 어떤 보호자?</h3>
                            <p>나의 양육 스타일을 확인해보세요</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <button class="slider-btn next-btn" onclick="moveSlide(1)">&#10095;</button>
    </div>
    
    <!-- 슬라이더 인디케이터 -->
    <div class="slider-dots">
        <span class="dot active" onclick="currentSlide(0)"></span>
        <span class="dot" onclick="currentSlide(1)"></span>
        <span class="dot" onclick="currentSlide(2)"></span>
    </div>
</div>

<script>
let currentIndex = 0;
const slides = document.querySelectorAll('.slide-item');
const dots = document.querySelectorAll('.dot');
const totalSlides = slides.length;

function moveSlide(direction) {
    currentIndex += direction;
    
    if (currentIndex < 0) {
        currentIndex = totalSlides - 1;
    } else if (currentIndex >= totalSlides) {
        currentIndex = 0;
    }
    
    updateSlider();
}

function currentSlide(index) {
    currentIndex = index;
    updateSlider();
}

function updateSlider() {
    const track = document.querySelector('.slider-track');
    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    
    // 인디케이터 업데이트
    dots.forEach((dot, index) => {
        dot.classList.remove('active');
        if (index === currentIndex) {
            dot.classList.add('active');
        }
    });
}

// 자동 슬라이드 (5초마다)
setInterval(() => {
    moveSlide(1);
}, 5000);
</script>

<?php render_footer(); ?>