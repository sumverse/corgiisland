<?php
// 엉덩코기 게시판 페이지
require_once 'config.php';
require_login();

$message = '';
$error = '';

// 상세보기 모드 확인
$view_mode = isset($_GET['id']) ? 'detail' : 'list';
$butt_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 댓글 작성 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_content'])) {
    $user_id = $_SESSION['user_id'];
    $comment_butt_id = intval($_POST['butt_id']);
    $comment_content = mysqli_real_escape_string($conn, trim($_POST['comment_content']));
    
    if (!empty($comment_content)) {
        $insert_comment = "INSERT INTO comments (target_type, target_id, user_id, content) 
                          VALUES ('butt', $comment_butt_id, $user_id, '$comment_content')";
        
        if (mysqli_query($conn, $insert_comment)) {
            header("Location: butt.php?id=$comment_butt_id&comment_added=1");
            exit();
        } else {
            $error = "댓글 등록 실패: " . mysqli_error($conn);
        }
    } else {
        $error = "댓글 내용을 입력해주세요.";
    }
}

// 댓글 삭제 처리
if (isset($_GET['delete_comment']) && is_numeric($_GET['delete_comment'])) {
    $comment_id = intval($_GET['delete_comment']);
    $user_id = $_SESSION['user_id'];
    
    // 본인 댓글인지 확인
    $check_query = "SELECT user_id, target_id FROM comments WHERE comment_id = $comment_id AND target_type = 'butt'";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && $comment_data = mysqli_fetch_assoc($check_result)) {
        if ($comment_data['user_id'] == $user_id) {
            $delete_query = "DELETE FROM comments WHERE comment_id = $comment_id";
            if (mysqli_query($conn, $delete_query)) {
                header("Location: butt.php?id=" . $comment_data['target_id'] . "&comment_deleted=1");
                exit();
            }
        }
    }
}

// 댓글 메시지
if (isset($_GET['comment_added'])) {
    $message = "댓글이 등록되었습니다!";
}
if (isset($_GET['comment_deleted'])) {
    $message = "댓글이 삭제되었습니다.";
}

// 사진 업로드 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
    $user_id = $_SESSION['user_id'];
    $caption = trim($_POST['caption']);
    
    // 사용자의 코기 정보 가져오기
    $corgi_query = "SELECT corgi_id FROM corgis WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $corgi_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $corgi_result = mysqli_stmt_get_result($stmt);
    
    if ($corgi = mysqli_fetch_assoc($corgi_result)) {
        $corgi_id = $corgi['corgi_id'];
        
        // 파일 업로드 처리
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($_FILES['photo']['size'] <= 5242880) {
                    $new_filename = time() . '_' . uniqid() . '.' . $ext;
                    $upload_path = UPLOAD_PATH . $new_filename;
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                        $photo_path = 'uploads/' . $new_filename;
                        $upload_date = date('Y-m-d');
                        
                        $insert_query = "INSERT INTO butt_corgi (corgi_id, user_id, photo_path, caption, upload_date) 
                                        VALUES (?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $insert_query);
                        mysqli_stmt_bind_param($stmt, "iisss", $corgi_id, $user_id, $photo_path, $caption, $upload_date);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $message = "사진이 업로드되었습니다!";
                            header("Location: butt.php?success=1");
                            exit();
                        } else {
                            $error = "업로드 중 오류가 발생했습니다.";
                        }
                    } else {
                        $error = "파일 업로드에 실패했습니다.";
                    }
                } else {
                    $error = "파일 크기는 5MB 이하여야 합니다.";
                }
            } else {
                $error = "jpg, png, gif 파일만 업로드 가능합니다.";
            }
        } else {
            $error = "사진을 선택해주세요.";
        }
    } else {
        $error = "코기 프로필을 먼저 등록해주세요.";
    }
}

// 성공 메시지 처리
if (isset($_GET['success'])) {
    $message = "사진이 업로드되었습니다!";
}

// 좋아요 처리
if (isset($_GET['like']) && is_numeric($_GET['like'])) {
    $like_butt_id = intval($_GET['like']);
    $user_id = $_SESSION['user_id'];
    
    $check_query = "SELECT reaction_id FROM reactions WHERE user_id = ? AND target_type = 'butt_corgi' AND target_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $like_butt_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        $like_query = "INSERT INTO reactions (user_id, target_type, target_id) VALUES (?, 'butt_corgi', ?)";
        $stmt = mysqli_prepare($conn, $like_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $like_butt_id);
        mysqli_stmt_execute($stmt);
        
        $update_query = "UPDATE butt_corgi SET likes_count = likes_count + 1 WHERE butt_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $like_butt_id);
        mysqli_stmt_execute($stmt);
    }
    
    header("Location: butt.php");
    exit();
}

// 상세보기
if ($view_mode == 'detail' && $butt_id > 0) {
    $detail_query = "SELECT b.*, c.corgi_name, u.nickname as user_nickname 
                     FROM butt_corgi b 
                     JOIN corgis c ON b.corgi_id = c.corgi_id 
                     JOIN users u ON b.user_id = u.user_id 
                     WHERE b.butt_id = $butt_id";
    $detail_result = mysqli_query($conn, $detail_query);
    $butt_item = mysqli_fetch_assoc($detail_result);
    
    if (!$butt_item) {
        header("Location: butt.php");
        exit();
    }
    
    // 댓글 조회
    $comments_query = "SELECT c.*, 
                      COALESCE(NULLIF(u.nickname, ''), u.name) as user_nickname
                      FROM comments c
                      JOIN users u ON c.user_id = u.user_id
                      WHERE c.target_type = 'butt' AND c.target_id = $butt_id
                      ORDER BY c.created_at ASC";
    $comments_result = mysqli_query($conn, $comments_query);
}

// 엉덩코기 목록 조회 (좋아요 순으로 정렬, 최대 6개)
$butt_query = "SELECT b.*, c.corgi_name, u.nickname as user_nickname 
               FROM butt_corgi b 
               JOIN corgis c ON b.corgi_id = c.corgi_id 
               JOIN users u ON b.user_id = u.user_id 
               ORDER BY b.likes_count DESC, b.created_at DESC 
               LIMIT 6";
$butt_result = mysqli_query($conn, $butt_query);

$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : '사용자';

render_header('엉덩코기 - ' . SITE_NAME, '', 'css/butt.css');
render_common_header($user_name);
render_navigation();
?>

<?php if ($view_mode == 'detail'): ?>
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo clean($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo clean($error); ?></div>
        <?php endif; ?>
        
        <div class="butt-detail">
            <div class="butt-detail-header">
                <h2 class="butt-detail-title"><?php echo clean($butt_item['corgi_name']); ?>의 엉덩이</h2>
                
                <div class="butt-detail-meta">
                    <span><?php echo clean($butt_item['user_nickname']); ?></span>
                    <span><?php echo format_datetime($butt_item['created_at']); ?></span>
                    <span>❤ <?php echo intval($butt_item['likes_count']); ?></span>
                </div>
            </div>
            
            <?php if ($butt_item['photo_path']): ?>
                <div class="butt-detail-image">
                    <img src="<?php echo clean($butt_item['photo_path']); ?>" alt="엉덩코기 사진">
                </div>
            <?php endif; ?>
            
            <?php if ($butt_item['caption']): ?>
                <div class="butt-detail-content">
                    <?php echo nl2br(clean($butt_item['caption'])); ?>
                </div>
            <?php endif; ?>
            
            <!-- 댓글 섹션 -->
            <div class="comments-section">
                <h3 class="comments-title">
                    댓글 <span class="comments-count"><?php echo mysqli_num_rows($comments_result); ?></span>
                </h3>
                
                <!-- 댓글 작성 폼 -->
                <form method="POST" class="comment-write-form">
                    <input type="hidden" name="butt_id" value="<?php echo $butt_id; ?>">
                    <textarea name="comment_content" placeholder="댓글을 입력하세요..." required></textarea>
                    <button type="submit" class="btn-comment-submit">댓글 등록</button>
                </form>
                
                <!-- 댓글 목록 -->
                <div class="comments-list">
                    <?php if (mysqli_num_rows($comments_result) > 0): ?>
                        <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <span class="comment-author"><?php echo clean($comment['user_nickname']); ?></span>
                                    <span class="comment-date"><?php echo format_datetime($comment['created_at']); ?></span>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(clean($comment['content'])); ?>
                                </div>
                                <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                                    <div class="comment-actions">
                                        <a href="?id=<?php echo $butt_id; ?>&delete_comment=<?php echo $comment['comment_id']; ?>" 
                                           class="btn-comment-delete"
                                           onclick="return confirm('댓글을 삭제하시겠습니까?')">삭제</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="comments-empty">
                            <p>첫 번째 댓글을 작성해보세요!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="butt-detail-actions">
                <a href="butt.php" class="btn">목록으로</a>
                <a href="?like=<?php echo $butt_id; ?>" class="btn" style="background: #e67e4d;">좋아요 ❤</a>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="container">
        <div class="page-title">
            <h2>엉덩코기 이번주 MVP</h2>
            <p>가장 귀여운 엉덩이를 뽐내보세요! </p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo clean($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo clean($error); ?></div>
        <?php endif; ?>
        
        <div class="butt-section">
            <?php if (mysqli_num_rows($butt_result) > 0): ?>
                <div class="butt-grid">
                    <?php 
                    $rank = 1;
                    while ($item = mysqli_fetch_assoc($butt_result)): 
                    ?>
                        <a href="butt.php?id=<?php echo intval($item['butt_id']); ?>" style="text-decoration: none; color: inherit;">
                            <div class="butt-card" data-rank="<?php echo $rank; ?>">
                                <div class="rank-badge rank-<?php echo $rank; ?>">
                                    <?php 
                                    if ($rank == 1) echo '🥇';
                                    else if ($rank == 2) echo '🥈';
                                    else if ($rank == 3) echo '🥉';
                                    ?>
                                </div>
                                
                                <div class="butt-image">
                                    <?php if ($item["photo_path"]): ?>
                                        <img src="<?php echo clean($item['photo_path']); ?>" alt="<?php echo clean($item['corgi_name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">사진 없음</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="butt-info">
                                    <h3 class="butt-corgi-name"><?php echo clean($item['corgi_name']); ?></h3>
                                    <p class="butt-user-nickname"><?php echo clean($item['user_nickname']); ?></p>
                                    
                                    <?php if ($item['caption']): ?>
                                        <p class="butt-caption"><?php echo clean($item['caption']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="butt-meta">
                                        <span class="like-button">
                                            ❤ <?php echo intval($item['likes_count']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>아직 등록된 사진이 없습니다</h3>
                    <p>첫 번째 엉덩코기를 올려보세요!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <button class="btn-write" onclick="openUploadModal()">사진 올리기</button>
    </div>

    <!-- 업로드 모달 -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>엉덩코기 사진 올리기</h3>
                <button class="close-modal" onclick="closeUploadModal()">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="modal-write-form">
                <div class="form-group">
                    <label>사진 선택 *</label>
                    <input type="file" name="photo" accept="image/*" required>
                    <small style="color: #999;">jpg, png, gif 파일 (최대 5MB)</small>
                </div>
                
                <div class="form-group">
                    <label>설명 (선택)</label>
                    <textarea name="caption" placeholder="사진에 대한 설명을 입력하세요"></textarea>
                </div>
                
                <button type="submit" name="upload" class="btn">업로드</button>
            </form>
        </div>
    </div>

    <script>
    function openUploadModal() {
        document.getElementById('uploadModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeUploadModal() {
        document.getElementById('uploadModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    document.getElementById('uploadModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeUploadModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeUploadModal();
        }
    });

    document.querySelector('.modal-write-form').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = '업로드 중...';
        }
    });
    </script>
<?php endif; ?>

<?php render_footer(); ?>