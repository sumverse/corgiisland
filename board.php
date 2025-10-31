<?php
// 코기talk 게시판
require_once 'config.php';
require_login();

$message = '';
$error = '';

// 카테고리 목록
$categories = [
    '사료/간식',
    '의류/패션', 
    '외출용품',
    '생활용품',
    '위생/미용',
    '건강/병원',
    '훈련/교육',
    '기타'
];

// 상세보기 모드 확인
$view_mode = isset($_GET['id']) ? 'detail' : 'list';
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 댓글 작성 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_content'])) {
    $user_id = $_SESSION['user_id'];
    $comment_post_id = intval($_POST['post_id']);
    $comment_content = mysqli_real_escape_string($conn, trim($_POST['comment_content']));
    
    if (!empty($comment_content)) {
        $insert_comment = "INSERT INTO comments (target_type, target_id, user_id, content) 
                          VALUES ('post', $comment_post_id, $user_id, '$comment_content')";
        
        if (mysqli_query($conn, $insert_comment)) {
            header("Location: board.php?id=$comment_post_id&comment_added=1");
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
    $check_query = "SELECT user_id, target_id FROM comments WHERE comment_id = $comment_id AND target_type = 'post'";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && $comment_data = mysqli_fetch_assoc($check_result)) {
        if ($comment_data['user_id'] == $user_id) {
            $delete_query = "DELETE FROM comments WHERE comment_id = $comment_id";
            if (mysqli_query($conn, $delete_query)) {
                header("Location: board.php?id=" . $comment_data['target_id'] . "&comment_deleted=1");
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

// 게시글 삭제 처리
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $user_id = $_SESSION['user_id'];
    
    // 본인 게시글인지 확인
    $check_query = "SELECT user_id FROM size_posts WHERE post_id = $delete_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && $post_data = mysqli_fetch_assoc($check_result)) {
        if ($post_data['user_id'] == $user_id) {
            // 게시글 삭제 (이미지는 삭제 안 함)
            $delete_query = "DELETE FROM size_posts WHERE post_id = $delete_id";
            if (mysqli_query($conn, $delete_query)) {
                header("Location: board.php?deleted=1");
                exit();
            } else {
                $error = "삭제 중 오류가 발생했습니다.";
            }
        } else {
            $error = "본인의 게시글만 삭제할 수 있습니다.";
        }
    }
}

// 삭제 완료 메시지
if (isset($_GET['deleted'])) {
    $message = "게시글이 삭제되었습니다.";
}

// 게시글 작성 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['title'])) {
    $user_id = $_SESSION['user_id'];
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    
    // 수정 모드인지 확인
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    
    if (empty($title) || empty($content) || empty($category)) {
        $error = "카테고리, 제목, 내용을 모두 입력해주세요.";
    } else {
        // 이미지 경로 처리 - 파일 복사 없이 경로만!
        $image_path = NULL;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $filename = $_FILES['image']['name'];
            // uploads/ 경로만 저장 (파일은 이미 거기 있으니까!)
            $image_path = 'uploads/' . $filename;
        }
        
        // 수정 모드
        if ($edit_id > 0) {
            // 본인 게시글인지 확인
            $check_query = "SELECT user_id FROM size_posts WHERE post_id = $edit_id";
            $check_result = mysqli_query($conn, $check_query);
            
            if ($check_result && $check_post = mysqli_fetch_assoc($check_result)) {
                if ($check_post['user_id'] == $user_id) {
                    // 이미지 업데이트
                    $image_update = "";
                    if ($image_path !== NULL) {
                        $image_update = ", image_path = '$image_path'";
                    }
                    
                    // 게시글 수정
                    $update_query = "UPDATE size_posts SET 
                                    title = '$title', 
                                    content = '$content', 
                                    product_name = '$category'
                                    $image_update
                                    WHERE post_id = $edit_id";
                    
                    if (mysqli_query($conn, $update_query)) {
                        header("Location: board.php?id=$edit_id&updated=1");
                        exit();
                    } else {
                        $error = "수정 실패: " . mysqli_error($conn);
                    }
                } else {
                    $error = "본인의 게시글만 수정할 수 있습니다.";
                }
            }
        } else {
            // 신규 작성
            // 코기 ID 가져오기
            $corgi_id = NULL;
            $corgi_query = "SELECT corgi_id FROM corgis WHERE user_id = $user_id LIMIT 1";
            $corgi_result = mysqli_query($conn, $corgi_query);
            if ($corgi_result && $corgi = mysqli_fetch_assoc($corgi_result)) {
                $corgi_id = intval($corgi['corgi_id']);
            }
            
            // 게시글 저장
            if ($corgi_id) {
                $insert_query = "INSERT INTO size_posts (user_id, corgi_id, title, content, image_path, product_name) 
                                VALUES ($user_id, $corgi_id, '$title', '$content', " . 
                                ($image_path ? "'$image_path'" : "NULL") . ", '$category')";
            } else {
                $insert_query = "INSERT INTO size_posts (user_id, title, content, image_path, product_name) 
                                VALUES ($user_id, '$title', '$content', " . 
                                ($image_path ? "'$image_path'" : "NULL") . ", '$category')";
            }
            
            if (mysqli_query($conn, $insert_query)) {
                header("Location: board.php?success=1");
                exit();
            } else {
                $error = "게시글 등록 실패: " . mysqli_error($conn);
            }
        }
    }
}

// 성공 메시지
if (isset($_GET['success'])) {
    $message = "게시글이 등록되었습니다!";
}
if (isset($_GET['updated'])) {
    $message = "게시글이 수정되었습니다!";
}

// 상세보기
if ($view_mode == 'detail' && $post_id > 0) {
    mysqli_query($conn, "UPDATE size_posts SET views = views + 1 WHERE post_id = $post_id");
    
    $detail_query = "SELECT p.*, 
                    COALESCE(NULLIF(u.nickname, ''), u.name) as user_nickname, 
                    c.corgi_name 
                    FROM size_posts p 
                    JOIN users u ON p.user_id = u.user_id 
                    LEFT JOIN corgis c ON p.corgi_id = c.corgi_id 
                    WHERE p.post_id = $post_id";
    $detail_result = mysqli_query($conn, $detail_query);
    $post = mysqli_fetch_assoc($detail_result);
    
    if (!$post) {
        header("Location: board.php");
        exit();
    }
    
    // 댓글 조회
    $comments_query = "SELECT c.*, 
                      COALESCE(NULLIF(u.nickname, ''), u.name) as user_nickname
                      FROM comments c
                      JOIN users u ON c.user_id = u.user_id
                      WHERE c.target_type = 'post' AND c.target_id = $post_id
                      ORDER BY c.created_at ASC";
    $comments_result = mysqli_query($conn, $comments_query);
}

// 검색 및 카테고리 필터
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$filter_category = isset($_GET['category']) ? mysqli_real_escape_string($conn, trim($_GET['category'])) : '';

$where = [];
if (!empty($search)) {
    $where[] = "(title LIKE '%$search%' OR content LIKE '%$search%')";
}
if (!empty($filter_category)) {
    $where[] = "product_name = '$filter_category'";
}

$where_clause = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : '';

$board_query = "SELECT p.*, 
                COALESCE(NULLIF(u.nickname, ''), u.name) as user_nickname, 
                c.corgi_name 
                FROM size_posts p 
                JOIN users u ON p.user_id = u.user_id 
                LEFT JOIN corgis c ON p.corgi_id = c.corgi_id 
                $where_clause
                ORDER BY p.created_at DESC";
$board_result = mysqli_query($conn, $board_query);

$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : '사용자';

// 헤더 렌더링
render_header('코기talk - ' . SITE_NAME);
?>
<link rel="stylesheet" href="css/board.css">
<?php
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
        
        <div class="board-detail">
            <div class="detail-header">
                <span class="board-category"><?php echo clean($post['product_name']); ?></span>
                <h2 class="detail-title"><?php echo clean($post['title']); ?></h2>
                
                <div class="detail-meta">
                    <span><?php echo clean($post['user_nickname']); ?></span>
                    <?php if ($post['corgi_name']): ?>
                        <span>코기: <?php echo clean($post['corgi_name']); ?></span>
                    <?php endif; ?>
                    <span><?php echo format_datetime($post['created_at']); ?></span>
                    <span>조회 <?php echo intval($post['views']); ?></span>
                </div>
            </div>
            
            <?php if ($post['image_path']): ?>
                <div class="detail-image">
                    <img src="<?php echo clean($post['image_path']); ?>" alt="게시글 이미지">
                </div>
            <?php endif; ?>
            
            <div class="detail-content">
                <?php echo nl2br(clean($post['content'])); ?>
            </div>
            
            <!-- 댓글 섹션 -->
            <div class="comments-section">
                <h3 class="comments-title">
                    댓글 <span class="comments-count"><?php echo mysqli_num_rows($comments_result); ?></span>
                </h3>
                
                <!-- 댓글 작성 폼 -->
                <form method="POST" class="comment-write-form">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
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
                                        <a href="?id=<?php echo $post_id; ?>&delete_comment=<?php echo $comment['comment_id']; ?>" 
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
            
            <div class="detail-actions">
                <a href="board.php" class="btn">목록으로</a>
                <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                    <button class="btn" onclick="openEditModal()">수정</button>
                    <a href="?delete=<?php echo intval($post['post_id']); ?>" 
                       class="btn" 
                       style="background: #dc3545;"
                       onclick="return confirm('정말 삭제하시겠습니까?')">삭제</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
        <!-- 수정 모달 -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>게시글 수정</h3>
                    <button class="close-modal" onclick="closeEditModal()">&times;</button>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="modal-write-form">
                    <input type="hidden" name="edit_id" value="<?php echo $post['post_id']; ?>">
                    
                    <div class="form-group">
                        <label>카테고리 *</label>
                        <select name="category" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $post['product_name'] == $cat ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>제목 *</label>
                        <input type="text" name="title" value="<?php echo clean($post['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>내용 *</label>
                        <textarea name="content" required><?php echo clean($post['content']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>이미지</label>
                        <input type="file" name="image" accept="image/*">
                        <?php if ($post['image_path']): ?>
                            <p style="font-size: 12px; color: #666; margin-top: 5px;">현재 이미지: <?php echo basename($post['image_path']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn-submit">수정 완료</button>
                </form>
            </div>
        </div>
        
        <script>
            function openEditModal() {
                document.getElementById('editModal').classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeEditModal() {
                document.getElementById('editModal').classList.remove('active');
                document.body.style.overflow = 'auto';
            }

            document.getElementById('editModal').addEventListener('click', function(event) {
                if (event.target === this) {
                    closeEditModal();
                }
            });
        </script>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="container">
        <div class="page-header">
            <h2>코기talk</h2>
            <p>코기 보호자들과 소통해요</p>
        </div>
        
        <div class="filter-section">
            <form method="GET" style="display: flex; gap: 10px; flex: 1; flex-wrap: wrap;">
                <select name="category" onchange="this.form.submit()">
                    <option value="">전체 카테고리</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $filter_category == $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" name="search" placeholder="제목, 내용으로 검색" value="<?php echo clean($search); ?>">
                <button type="submit">검색</button>
            </form>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo clean($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo clean($error); ?></div>
        <?php endif; ?>
        
        <!-- 게시글 목록 영역 -->
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
                    <h3>아직 등록된 게시글이 없습니다</h3>
                    <p>첫 번째 정보를 공유해보세요!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 게시글 작성 버튼 -->
        <button class="btn-write" onclick="openModal()">게시글 작성</button>
    </div>

    <!-- 모달 -->
    <div id="writeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>게시글 작성</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="modal-write-form">
                <div class="form-group">
                    <label>카테고리 *</label>
                    <select name="category" required>
                        <option value="">선택하세요</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>제목 *</label>
                    <input type="text" name="title" placeholder="제목을 입력하세요" required>
                </div>
                
                <div class="form-group">
                    <label>내용 *</label>
                    <textarea name="content" placeholder="내용을 입력하세요" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>이미지</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                
                <button type="submit" class="btn-submit">게시글 등록</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('writeModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('writeModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        document.getElementById('writeModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        document.querySelector('.modal-write-form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '등록 중...';
            }
        });

        <?php if ($error): ?>
            openModal();
        <?php endif; ?>
    </script>
<?php endif; ?>

<?php render_footer(); ?>