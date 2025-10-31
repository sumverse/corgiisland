<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_login();

$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : '사용자';

$message = '';
$message = '';
$result_data = null;

// test_type 가져오기
if (isset($_POST['test_type'])) {
    $test_type = $_POST['test_type'];
} elseif (isset($_GET['type'])) {
    $test_type = $_GET['type'];
} else {
    $test_type = 'select';
}

// MBTI 성격 유형 설명
$mbti_types = [
    'ENFP' => ['이름' => '활발한 소셜버터플 코기', '설명' => '에너지 넘치고 친화력 최고! 모든 강아지와 친구가 되고 싶어하는 우리 코기'],
    'ENFJ' => ['이름' => '다정한 리더 코기', '설명' => '친구들을 잘 챙기고 모임의 중심이 되는 따뜻한 코기'],
    'ENTP' => ['이름' => '호기심 대장 코기', '설명' => '새로운 것에 도전하고 탐험하길 좋아하는 똑똑한 코기'],
    'ENTJ' => ['이름' => '당당한 알파 코기', '설명' => '자신감 넘치고 주도적인 카리스마 있는 코기'],
    'INFP' => ['이름' => '감성적인 예술가 코기', '설명' => '조용하지만 감수성이 풍부한 낭만적인 코기'],
    'INFJ' => ['이름' => '신비로운 현자 코기', '설명' => '깊은 사색을 즐기는 지혜로운 코기'],
    'INTP' => ['이름' => '천재 과학자 코기', '설명' => '혼자만의 시간을 즐기는 독립적이고 영리한 코기'],
    'INTJ' => ['이름' => '전략가 코기', '설명' => '계획적이고 논리적인 똑똑한 코기'],
    'ESFP' => ['이름' => '파티광 코기', '설명' => '즐거운 분위기를 만드는 재밌는 코기'],
    'ESFJ' => ['이름' => '다정한 보호자 코기', '설명' => '가족을 사랑하고 헌신적인 따뜻한 코기'],
    'ESTP' => ['이름' => '스릴을 즐기는 모험가 코기', '설명' => '활동적이고 대담한 에너지 넘치는 코기'],
    'ESTJ' => ['이름' => '책임감 강한 대장 코기', '설명' => '규칙을 잘 지키고 믿음직한 코기'],
    'ISFP' => ['이름' => '온화한 예술가 코기', '설명' => '조용하고 평화로운 부드러운 코기'],
    'ISFJ' => ['이름' => '헌신적인 수호자 코기', '설명' => '가족에게 충실하고 안정적인 코기'],
    'ISTP' => ['이름' => '쿨한 장인 코기', '설명' => '독립적이고 차분한 실용적인 코기'],
    'ISTJ' => ['이름' => '성실한 모범생 코기', '설명' => '책임감 있고 신뢰할 수 있는 코기']
];

// 애착 유형 설명
$love_types = [
    'secure' => ['이름' => '안정형 코기', '설명' => '주인을 신뢰하고 안정적인 애착을 가진 코기입니다. 혼자 있어도 괜찮고, 주인이 돌아오면 반갑게 맞이하는 건강한 애착 관계를 보입니다.'],
    'anxious' => ['이름' => '불안형 코기', '설명' => '주인과의 분리를 힘들어하고 불안해하는 코기입니다. 주인에게 매우 의존적이며, 항상 관심과 애정을 갈구합니다.'],
    'avoidant' => ['이름' => '회피형 코기', '설명' => '독립적이고 혼자 있는 것을 편안해하는 코기입니다. 주인과의 친밀한 접촉보다는 혼자만의 시간을 더 좋아합니다.'],
    'confused' => ['이름' => '혼란형 코기', '설명' => '애착 행동이 일관되지 않고 예측하기 어려운 코기입니다. 때로는 애정을 원하다가도 갑자기 거리를 두는 모습을 보입니다.']
];

// 보호자 유형 설명
$owner_types = [
    'overprotective' => ['이름' => '과보호형 보호자', '설명' => '코기가 세상의 전부! 24시간 케어하는 보호자입니다. 코기의 모든 것을 관리하고 최고의 것만 제공하려 합니다.'],
    'balanced' => ['이름' => '균형형 보호자', '설명' => '케어와 자유의 완벽한 밸런스를 가진 보호자입니다. 필요할 때 보살피지만 코기의 독립성도 존중합니다.'],
    'free' => ['이름' => '방목형 보호자', '설명' => '코기도 독립적인 존재, 자유롭게 키우는 보호자입니다. 코기가 스스로 결정하고 행동할 수 있도록 합니다.'],
    'strict' => ['이름' => '엄격형 보호자', '설명' => '규칙과 훈련 중시, 체계적으로 양육하는 보호자입니다. 명확한 규칙을 세우고 일관되게 훈련합니다.'],
    'butler' => ['이름' => '집사형 보호자', '설명' => '코기님을 모시는 충성스러운 집사입니다. 코기의 요구를 최우선으로 생각하고 헌신적으로 보살핍니다.'],
    'friend' => ['이름' => '친구형 보호자', '설명' => '코기와 동등한 친구 관계를 유지하는 보호자입니다. 서로를 존중하며 평등한 관계를 추구합니다.']
];

// 테스트 결과 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_test'])) {
    $answers = $_POST['answer'] ?? [];
    $submit_type = $_POST['test_type'];
    
    if (count($answers) >= 10) {
        
        if ($submit_type == 'mbti') {
            // MBTI 처리
            $scores = ['E' => 0, 'I' => 0, 'S' => 0, 'N' => 0, 'T' => 0, 'F' => 0, 'J' => 0, 'P' => 0];
            
            foreach ($answers as $question_id => $answer) {
                $query = "SELECT trait_type FROM personality_questions WHERE question_id = ? AND test_type = 'mbti'";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $question_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {
                    $trait = $row['trait_type'];
                    if ($answer == 'A') {
                        $scores[$trait[0]]++;
                    } else {
                        $scores[$trait[1]]++;
                    }
                }
            }
            
            $personality_type = '';
            $personality_type .= ($scores['E'] >= $scores['I']) ? 'E' : 'I';
            $personality_type .= ($scores['S'] >= $scores['N']) ? 'S' : 'N';
            $personality_type .= ($scores['T'] >= $scores['F']) ? 'T' : 'F';
            $personality_type .= ($scores['J'] >= $scores['P']) ? 'J' : 'P';
            
            $result_data = [
                'type' => $personality_type,
                'name' => $mbti_types[$personality_type]['이름'],
                'desc' => $mbti_types[$personality_type]['설명']
            ];
            
        } elseif ($submit_type == 'love') {
            // 애착 유형 처리
            $scores = ['S' => 0, 'A' => 0, 'X' => 0];
            
            foreach ($answers as $question_id => $answer) {
                $query = "SELECT trait_type FROM personality_questions WHERE question_id = ? AND test_type = 'love'";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $question_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {
                    $trait = $row['trait_type'];
                    if ($answer == 'A') {
                        $scores[$trait[0]]++;
                    } else {
                        $scores[$trait[1]]++;
                    }
                }
            }
            
            arsort($scores);
            $dominant = array_key_first($scores);
            
            if ($dominant == 'S' && $scores['S'] >= 6) {
                $love_type = 'secure';
            } elseif ($dominant == 'X' || ($scores['X'] >= 4)) {
                $love_type = 'anxious';
            } elseif ($dominant == 'A' && $scores['A'] >= 6) {
                $love_type = 'avoidant';
            } else {
                $love_type = 'confused';
            }
            
            // type만 한글로 (name 제거)
            $result_data = [
                'type' => $love_types[$love_type]['이름'],
                'desc' => $love_types[$love_type]['설명']
            ];
            
        } elseif ($submit_type == 'owner') {
            // 보호자 유형 처리
            $scores = ['S' => 0, 'T' => 0, 'O' => 0, 'H' => 0, 'B' => 0, 'F' => 0];
            
            foreach ($answers as $question_id => $answer) {
                $query = "SELECT trait_type FROM personality_questions WHERE question_id = ? AND test_type = 'owner'";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $question_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {
                    $trait = $row['trait_type'];
                    if ($answer == 'A') {
                        $scores[$trait[0]]++;
                    } else {
                        $scores[$trait[1]]++;
                    }
                }
            }
            
            $strict_score = $scores['S'];
            $overprotect_score = $scores['O'];
            $butler_score = $scores['B'];
            
            if ($overprotect_score >= 3) {
                $owner_type = 'overprotective';
            } elseif ($strict_score >= 3) {
                $owner_type = 'strict';
            } elseif ($butler_score >= 1 && $overprotect_score >= 2) {
                $owner_type = 'butler';
            } elseif ($scores['F'] >= 1 && $strict_score <= 2) {
                $owner_type = 'friend';
            } elseif ($scores['T'] >= 3 || $scores['H'] >= 3) {
                $owner_type = 'free';
            } else {
                $owner_type = 'balanced';
            }
            
            // type만 한글로 (name 제거)
            $result_data = [
                'type' => $owner_types[$owner_type]['이름'],
                'desc' => $owner_types[$owner_type]['설명']
            ];
        }
    } else {
        $error = "모든 질문에 답변해주세요. (현재 " . count($answers) . "개)";
    }
}

// 선택된 테스트의 질문 가져오기
if ($test_type != 'select' && !$result_data) {
    $questions_query = "SELECT * FROM personality_questions WHERE test_type = ? ORDER BY question_order";
    $stmt = mysqli_prepare($conn, $questions_query);
    mysqli_stmt_bind_param($stmt, "s", $test_type);
    mysqli_stmt_execute($stmt);
    $questions_result = mysqli_stmt_get_result($stmt);
}

render_header('코기 테스트 - ' . SITE_NAME);
?>
<link rel="stylesheet" href="css/test.css">
<?php
render_common_header($user_name);
render_navigation();
?>

<div class="container">
    <div class="page-title">
        <h2>🐕 코기 테스트</h2>
        <p>우리 코기를 더 잘 알아볼까요?</p>
    </div>
    
    <?php if ($result_data): ?>
        <!-- 결과 화면 -->
        <div class="result-section">
            <div class="result-icon">🎉</div>
            <div class="result-title"><?php echo clean($result_data['type']); ?></div>
            
            <?php if (isset($result_data['name'])): ?>
                <div class="result-subtitle"><?php echo clean($result_data['name']); ?></div>
            <?php endif; ?>
            
            <p class="result-description"><?php echo clean($result_data['desc']); ?></p>
            
            <div class="result-actions">
                <a href="test.php" class="btn-retry">다른 테스트 하기</a>
                <a href="index.php" class="btn-home">홈으로</a>
            </div>
        </div>
        
    <?php elseif ($test_type == 'select'): ?>
        <!-- 테스트 선택 화면 -->
        <div class="test-select-section">
            <h2 style="text-align: center; color: #333; margin-bottom: 10px; font-family: 'GumiRomance', sans-serif;">어떤 테스트를 하시겠어요?</h2>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">아래에서 원하는 테스트를 선택하세요</p>
            
            <div class="test-grid">
                <a href="test.php?type=love" style="text-decoration: none; color: inherit;">
                    <div class="test-card">
                        <div class="test-icon">💕</div>
                        <h3>애착 유형 테스트</h3>
                        <p>우리 코기와의 애착 관계를 알아보세요</p>
                    </div>
                </a>
                
                <a href="test.php?type=mbti" style="text-decoration: none; color: inherit;">
                    <div class="test-card">
                        <div class="test-icon">🎭</div>
                        <h3>코기 MBTI 테스트</h3>
                        <p>우리 코기의 성격 유형을 알아보세요</p>
                    </div>
                </a>
                
                <a href="test.php?type=owner" style="text-decoration: none; color: inherit;">
                    <div class="test-card">
                        <div class="test-icon">👤</div>
                        <h3>나는 어떤 보호자?</h3>
                        <p>나의 양육 스타일을 확인해보세요</p>
                    </div>
                </a>
            </div>
        </div>
        
    <?php else: ?>
        <!-- 테스트 진행 화면 -->
        <div class="test-section">
            <div style="margin-bottom: 20px;">
                <a href="test.php" style="color: #e67e4d; text-decoration: none; font-size: 14px;">← 테스트 선택으로 돌아가기</a>
            </div>
            
            <div class="test-header">
                <h2>
                    <?php 
                    if ($test_type == 'mbti') echo '🎭 코기 MBTI 테스트';
                    elseif ($test_type == 'love') echo '💕 애착 유형 테스트';
                    elseif ($test_type == 'owner') echo '👤 나는 어떤 보호자?';
                    ?>
                </h2>
                <p>총 <?php echo mysqli_num_rows($questions_result); ?>개의 질문</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="test_type" value="<?php echo $test_type; ?>">
                
                <?php 
                $count = 1;
                while ($question = mysqli_fetch_assoc($questions_result)): 
                ?>
                    <div class="question-card">
                        <div class="question-number">질문 <?php echo $count; ?></div>
                        <div class="question-text"><?php echo clean($question['question_text']); ?></div>
                        
                        <div class="answer-options">
                            <div class="answer-option">
                                <input type="radio" 
                                       name="answer[<?php echo $question['question_id']; ?>]" 
                                       value="A" 
                                       id="q<?php echo $question['question_id']; ?>_a" 
                                       required>
                                <label for="q<?php echo $question['question_id']; ?>_a">
                                    <?php echo clean($question['option_a']); ?>
                                </label>
                            </div>
                            
                            <div class="answer-option">
                                <input type="radio" 
                                       name="answer[<?php echo $question['question_id']; ?>]" 
                                       value="B" 
                                       id="q<?php echo $question['question_id']; ?>_b" 
                                       required>
                                <label for="q<?php echo $question['question_id']; ?>_b">
                                    <?php echo clean($question['option_b']); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                <?php 
                $count++;
                endwhile; 
                ?>
                
                <div class="submit-section">
                    <button type="submit" name="submit_test" class="btn-submit">결과 보기</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php render_footer(); ?>