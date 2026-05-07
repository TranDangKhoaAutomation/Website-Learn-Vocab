<?php
require_once __DIR__ . '/includes/settings.php';

$loggedIn = !empty($_SESSION['user_id']);
enforce_maintenance_mode();
$registrationEnabled = settings_enabled('registration_enabled', true);

// ── System stats ──
$sysStats = ['users' => 0, 'sets' => 0, 'cards' => 0, 'sessions' => 0];
if ($pdo) {
    $sysStats['users']    = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $sysStats['sets']     = (int) $pdo->query('SELECT COUNT(*) FROM vocabulary_sets WHERE status = "active"')->fetchColumn();
    $sysStats['cards']    = (int) $pdo->query('SELECT COUNT(*) FROM flashcards')->fetchColumn();
    $sysStats['sessions'] = (int) $pdo->query('SELECT COUNT(*) FROM study_sessions')->fetchColumn();
}

// ── Latest public sets ──
$latestSets = [];
$publicLibraryEnabled = settings_enabled('public_library_enabled', true);
if ($pdo && $publicLibraryEnabled) {
    $stmt = $pdo->prepare('
        SELECT s.*, u.name owner_name, c.name category_name,
               COALESCE(fc.card_count, 0) AS card_count
        FROM vocabulary_sets s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN categories c ON c.id = s.category_id
        LEFT JOIN (
            SELECT set_id, COUNT(*) AS card_count FROM flashcards GROUP BY set_id
        ) fc ON fc.set_id = s.id
        WHERE s.visibility = "public" AND s.status = "active"
        ORDER BY s.updated_at DESC
        LIMIT 6
    ');
    $stmt->execute();
    $latestSets = $stmt->fetchAll();
}

// ── Leaderboard ──
$leaderboardRows = [];
$leaderboardEnabled = settings_enabled('leaderboard_enabled', true);
if ($pdo && $leaderboardEnabled) {
    $stmt = $pdo->query('
        SELECT u.name, COALESCE(SUM(up.correct_count + up.wrong_count), 0) total
        FROM users u
        LEFT JOIN user_progress up ON up.user_id = u.id
        GROUP BY u.id
        HAVING total > 0
        ORDER BY total DESC
        LIMIT 5
    ');
    $leaderboardRows = $stmt->fetchAll();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(site_name()) ?> - <?= e(site_tagline()) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="public-page home-page" data-base-url="<?= BASE_URL ?>">
<nav class="public-nav">
    <a class="public-brand" href="<?= BASE_URL ?>">
        <span class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></span>
        <span><?= e(site_name()) ?></span>
    </a>
    <div class="public-nav-links">
        <a href="#features">Tính năng</a>
        <a href="#modes">Chế độ học</a>
        <a href="#paths">Lộ trình</a>
        <a href="#founder">Người sáng lập</a>
        <button class="public-theme-toggle" type="button" data-theme-toggle="icon" aria-label="Dark / Light mode" title="Dark / Light mode">
            <i class="bi bi-moon"></i>
        </button>
        <?php if ($loggedIn): ?>
            <a class="btn btn-primary" href="<?= app_url('dashboard.php') ?>">Vào Dashboard</a>
        <?php else: ?>
            <a href="<?= app_url('login.php') ?>">Đăng nhập</a>
            <?php if ($registrationEnabled): ?>
                <a class="btn btn-primary" href="<?= app_url('register.php') ?>">Bắt đầu miễn phí</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</nav>

<header class="home-hero">
    <div class="home-hero-inner">
        <div class="home-hero-copy">
            <span class="hero-kicker"><i class="bi bi-stars"></i> Website học tiếng Anh miễn phí</span>
            <h1>Ghi nhớ từ vựng bằng flashcard, quiz và mini game.</h1>
            <p>Tạo bộ từ riêng, học theo lớp, cấp quyền cho người khác và luyện phát âm bằng Web Speech API ngay trong trình duyệt.</p>
            <div class="hero-actions">
                <?php if ($loggedIn): ?>
                    <a class="btn btn-primary btn-lg" href="<?= app_url('dashboard.php') ?>">Tiếp tục học</a>
                    <a class="btn btn-light btn-lg" href="<?= app_url('pages/create_set.php') ?>">Tạo bộ từ</a>
                <?php else: ?>
                    <?php if ($registrationEnabled): ?>
                        <a class="btn btn-primary btn-lg" href="<?= app_url('register.php') ?>">Tạo tài khoản miễn phí</a>
                    <?php endif; ?>
                    <a class="btn btn-light btn-lg" href="<?= app_url('login.php') ?>">Đăng nhập</a>
                <?php endif; ?>
            </div>
            <div class="home-trust-row">
                <span><strong>6</strong> chế độ học</span>
                <span><strong>30+</strong> flashcards mẫu</span>
                <span><strong>Viewer · Editor · Admin</strong> phân quyền</span>
            </div>
        </div>
        <div class="hero-vocab-stack" aria-label="Ví dụ flashcard">
            <div class="hero-word-card primary">
                <span>word</span>
                <strong>improve</strong>
                <em>/ɪmˈpruːv/</em>
            </div>
            <div class="hero-word-card">
                <span>meaning</span>
                <strong>cải thiện</strong>
                <em>You can improve every day.</em>
            </div>
            <div class="hero-word-card accent">
                <span>mode</span>
                <strong>Learn</strong>
                <em>Retry wrong answers</em>
            </div>
        </div>
    </div>
</header>

<main>
    <?php if ($pdo): ?>
    <section class="sys-stats-section">
        <div class="sys-stats-grid">
            <div class="sys-stat-card">
                <i class="bi bi-people-fill"></i>
                <strong><?= number_format($sysStats['users']) ?></strong>
                <span>Người dùng</span>
            </div>
            <div class="sys-stat-card">
                <i class="bi bi-collection-fill"></i>
                <strong><?= number_format($sysStats['sets']) ?></strong>
                <span>Bộ từ vựng</span>
            </div>
            <div class="sys-stat-card">
                <i class="bi bi-card-text"></i>
                <strong><?= number_format($sysStats['cards']) ?></strong>
                <span>Flashcards</span>
            </div>
            <div class="sys-stat-card">
                <i class="bi bi-play-circle-fill"></i>
                <strong><?= number_format($sysStats['sessions']) ?></strong>
                <span>Phiên học</span>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="home-cards-section" id="features">
        <div class="section-heading centered">
            <span>Giới thiệu nhanh</span>
            <h2>Mọi thứ cần thiết để tự học hoặc dạy một lớp nhỏ</h2>
            <p>Trang Home này giúp người dùng mới hiểu app dùng để làm gì trước khi đăng nhập.</p>
        </div>
        <div class="home-info-grid">
            <article class="home-info-card">
                <i class="bi bi-journal-plus"></i>
                <h3>Tạo bộ từ vựng</h3>
                <p>Thêm nhiều flashcard gồm từ, nghĩa, phiên âm, câu ví dụ và ảnh minh họa.</p>
            </article>
            <article class="home-info-card">
                <i class="bi bi-volume-up"></i>
                <h3>Nghe phát âm</h3>
                <p>Flashcards có nút loa, Auto speak và đọc câu ví dụ bằng SpeechSynthesis.</p>
            </article>
            <article class="home-info-card">
                <i class="bi bi-people"></i>
                <h3>Lớp học và chia sẻ</h3>
                <p>Bộ từ có thể private, public, cấp cho lớp hoặc chia sẻ riêng theo vai trò.</p>
            </article>
            <article class="home-info-card">
                <i class="bi bi-graph-up-arrow"></i>
                <h3>Theo dõi tiến độ</h3>
                <p>Lưu câu đúng/sai, điểm test và lịch sử học để biết mình cần ôn lại gì.</p>
            </article>
        </div>
    </section>

    <section class="mode-showcase" id="modes">
        <div class="section-heading">
            <span>Chọn cách học</span>
            <h2>Không bị ép vào một kiểu học duy nhất</h2>
            <p>Mỗi chế độ có màn chọn bài riêng. Người dùng chọn bộ từ trước rồi mới bắt đầu luyện tập.</p>
        </div>
        <div class="mode-card-grid">
            <article class="mode-card">
                <i class="bi bi-card-text"></i>
                <h3>Flashcards</h3>
                <p>Lật thẻ, nghe từ và đánh dấu biết/chưa biết.</p>
            </article>
            <article class="mode-card">
                <i class="bi bi-mortarboard"></i>
                <h3>Learn</h3>
                <p>Trộn câu hỏi, lưu câu sai và học lại tự động.</p>
            </article>
            <article class="mode-card">
                <i class="bi bi-ui-checks-grid"></i>
                <h3>Test</h3>
                <p>Multiple choice, true/false, fill blank và written answer.</p>
            </article>
            <article class="mode-card">
                <i class="bi bi-boxes"></i>
                <h3>Blocks</h3>
                <p>Ghép block từ và nghĩa trong thời gian ngắn nhất.</p>
            </article>
            <article class="mode-card">
                <i class="bi bi-rocket-takeoff"></i>
                <h3>Blast</h3>
                <p>Chọn đáp án nhanh trong 60 giây để luyện phản xạ.</p>
            </article>
            <article class="mode-card">
                <i class="bi bi-intersect"></i>
                <h3>Match</h3>
                <p>Ghép cặp chính xác, tính điểm và thời gian hoàn thành.</p>
            </article>
        </div>
    </section>

    <section class="learning-path-section" id="paths">
        <div class="section-heading centered">
            <span>Lộ trình học gợi ý</span>
            <h2>Chọn mục tiêu trước, app giúp bạn đi theo từng bước rõ ràng</h2>
            <p>Mỗi nhóm người học có thể bắt đầu bằng cách khác nhau: làm quen từ cơ bản, ôn thi, hoặc tổ chức bài học cho lớp.</p>
        </div>
        <div class="learning-path-grid">
            <article class="learning-path-card featured">
                <span class="path-number">01</span>
                <i class="bi bi-person-walking"></i>
                <h3>Người mới bắt đầu</h3>
                <p>Bắt đầu bằng bộ từ nhỏ, học mặt trước/mặt sau, nghe phát âm rồi chuyển sang Learn để kiểm tra nhớ nghĩa.</p>
                <ul>
                    <li>5-10 từ mỗi lượt học</li>
                    <li>Nghe phát âm trước khi trả lời</li>
                    <li>Ôn lại ngay những câu chưa nhớ</li>
                </ul>
            </article>
            <article class="learning-path-card">
                <span class="path-number">02</span>
                <i class="bi bi-award"></i>
                <h3>Ôn thi và kiểm tra</h3>
                <p>Dùng Test để trộn dạng câu hỏi, xem điểm theo từng bộ từ và phát hiện nhóm từ còn yếu để học lại.</p>
                <ul>
                    <li>Multiple choice, true/false, fill blank</li>
                    <li>Lưu điểm theo từng lượt test</li>
                    <li>Chuyển câu sai về Learn mode</li>
                </ul>
            </article>
            <article class="learning-path-card">
                <span class="path-number">03</span>
                <i class="bi bi-people"></i>
                <h3>Lớp học hoặc nhóm nhỏ</h3>
                <p>Giáo viên tạo bộ từ, chia sẻ cho lớp, cấp quyền editor khi cần cùng nhau bổ sung ví dụ và hình minh họa.</p>
                <ul>
                    <li>Tạo lớp, mời thành viên</li>
                    <li>Chia sẻ bộ từ theo quyền</li>
                    <li>Theo dõi tiến độ học của từng người</li>
                </ul>
            </article>
        </div>
    </section>

    <section class="home-split-section">
        <div class="split-visual">
            <div class="study-glass-card large">
                <span>Today</span>
                <strong>24 từ đã ôn</strong>
                <p>Đúng 18 · Sai 6 · Cần học lại 4</p>
            </div>
            <div class="study-glass-card small top">
                <i class="bi bi-volume-up"></i>
                <span>Auto speak</span>
            </div>
            <div class="study-glass-card small bottom">
                <i class="bi bi-keyboard"></i>
                <span>Keyboard ready</span>
            </div>
        </div>
        <div class="split-copy">
            <span>Trải nghiệm học nhanh</span>
            <h2>Ít thao tác hơn, tập trung vào việc nhớ từ.</h2>
            <p>Người học có thể dùng phím tắt để lật thẻ, chọn đáp án, chuyển câu và nghe phát âm mà không cần rời tay khỏi bàn phím.</p>
            <div class="mini-feature-list">
                <div><i class="bi bi-check2"></i> Auto speak đọc từ khi chuyển thẻ</div>
                <div><i class="bi bi-check2"></i> Learn lưu câu sai để học lại</div>
                <div><i class="bi bi-check2"></i> Test lưu điểm theo từng bộ từ</div>
            </div>
        </div>
    </section>

    <section class="daily-routine-section">
        <div class="daily-routine-layout">
            <div class="section-heading">
                <span>Kế hoạch 15 phút mỗi ngày</span>
                <h2>Không cần học lâu, quan trọng là quay lại đều và biết mình đang yếu ở đâu.</h2>
                <p>Home giới thiệu một nhịp học ngắn để người mới hiểu cách dùng app trong thực tế: chọn bộ từ, nghe, trả lời, lưu tiến độ và ôn lại.</p>
            </div>
            <div class="routine-board">
                <div class="routine-card active">
                    <strong>03 phút</strong>
                    <span>Warm up</span>
                    <p>Lướt Flashcards, nghe phát âm và đánh dấu những từ còn lạ.</p>
                </div>
                <div class="routine-card">
                    <strong>07 phút</strong>
                    <span>Practice</span>
                    <p>Chuyển sang Learn hoặc Match để buộc não nhớ nghĩa thay vì chỉ nhìn lại.</p>
                </div>
                <div class="routine-card">
                    <strong>03 phút</strong>
                    <span>Check</span>
                    <p>Làm nhanh vài câu Test để xem điểm và lưu lịch sử học.</p>
                </div>
                <div class="routine-card">
                    <strong>02 phút</strong>
                    <span>Review</span>
                    <p>Ôn riêng các câu sai, thêm ví dụ mới nếu bộ từ còn thiếu ngữ cảnh.</p>
                </div>
            </div>
        </div>
    </section>

    <?php if ($leaderboardEnabled && $leaderboardRows): ?>
    <section class="leaderboard-section">
        <div class="section-heading centered">
            <span>Bảng xếp hạng</span>
            <h2>Những người học chăm chỉ nhất</h2>
            <p>Dữ liệu được cập nhật theo thời gian thực từ hệ thống.</p>
        </div>
        <div class="leaderboard-list">
            <?php foreach ($leaderboardRows as $i => $row):
                $rank = $i + 1;
                $rankIcon = match($rank) {
                    1 => 'bi-trophy-fill text-warning',
                    2 => 'bi-trophy-fill text-secondary',
                    3 => 'bi-trophy-fill',
                    default => '',
                };
                $rankClass = $rank <= 3 ? 'top-three' : '';
            ?>
            <div class="leaderboard-row <?= $rankClass ?>">
                <span class="leaderboard-rank">
                    <?php if ($rank <= 3): ?>
                        <i class="bi <?= $rankIcon ?>"></i>
                    <?php else: ?>
                        <?= $rank ?>
                    <?php endif; ?>
                </span>
                <span class="leaderboard-name"><?= e($row['name']) ?></span>
                <span class="leaderboard-total"><?= number_format((int) $row['total']) ?> câu đã học</span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="learning-flow-section">
        <div class="flow-copy">
            <span>Quy trình rõ ràng</span>
            <h2>Bắt đầu từ bộ từ, sau đó chọn mode học phù hợp.</h2>
            <p>Giao diện được làm theo kiểu dashboard để người học quay lại mỗi ngày mà không bị rối.</p>
        </div>
        <div class="flow-steps">
            <div><strong>01</strong><span>Tạo hoặc chọn bộ từ</span></div>
            <div><strong>02</strong><span>Chọn Flashcards, Learn, Test hoặc game</span></div>
            <div><strong>03</strong><span>Lưu tiến độ và học lại câu sai</span></div>
        </div>
    </section>

    <section class="permission-section">
        <div class="section-heading centered">
            <span>Chia sẻ linh hoạt</span>
            <h2>Một bộ từ có thể dùng cho cá nhân, lớp học hoặc nhóm nhỏ</h2>
            <p>Chủ bộ từ kiểm soát ai được học, ai được sửa và ai được cấp quyền tiếp.</p>
        </div>
        <div class="permission-grid">
            <article>
                <i class="bi bi-eye"></i>
                <h3>Viewer</h3>
                <p>Chỉ học và lưu tiến độ cá nhân.</p>
            </article>
            <article>
                <i class="bi bi-pencil-square"></i>
                <h3>Editor</h3>
                <p>Học, thêm thẻ, sửa thẻ và cải thiện nội dung bài.</p>
            </article>
            <article>
                <i class="bi bi-shield-lock"></i>
                <h3>Admin</h3>
                <p>Sửa nội dung và quản lý quyền truy cập cho người khác.</p>
            </article>
        </div>
    </section>

    <section class="topic-library-section">
        <div class="section-heading centered">
            <span>Gợi ý nội dung học</span>
            <h2>Home có thể dẫn người dùng vào nhiều chủ đề từ vựng quen thuộc</h2>
            <p>Người mới thường chưa biết nên tạo bộ từ nào trước. Các nhóm chủ đề mẫu giúp họ hình dung nhanh nội dung có thể học trong app.</p>
        </div>
        <div class="topic-library-grid">
            <article>
                <i class="bi bi-chat-dots"></i>
                <h3>Giao tiếp hằng ngày</h3>
                <p>Chào hỏi, hỏi đường, mua sắm, đặt lịch hẹn và các mẫu câu phản xạ nhanh.</p>
                <span>daily · speaking · phrase</span>
            </article>
            <article>
                <i class="bi bi-briefcase"></i>
                <h3>Công việc văn phòng</h3>
                <p>Email, meeting, deadline, báo cáo, teamwork và những từ thường gặp trong môi trường làm việc.</p>
                <span>business · email · meeting</span>
            </article>
            <article>
                <i class="bi bi-airplane"></i>
                <h3>Du lịch và dịch vụ</h3>
                <p>Sân bay, khách sạn, nhà hàng, phương tiện, tình huống cần hỏi hoặc xử lý khi đi xa.</p>
                <span>travel · hotel · airport</span>
            </article>
            <article>
                <i class="bi bi-mortarboard"></i>
                <h3>Học thuật cơ bản</h3>
                <p>Từ vựng đọc hiểu, mô tả biểu đồ, trình bày ý kiến và các cụm từ dùng trong bài viết.</p>
                <span>academic · essay · reading</span>
            </article>
            <article>
                <i class="bi bi-lightbulb"></i>
                <h3>Cụm từ dễ nhầm</h3>
                <p>Phân biệt các từ gần nghĩa, collocation thường gặp và ví dụ ngắn để nhớ đúng ngữ cảnh.</p>
                <span>confusing · collocation</span>
            </article>
            <article>
                <i class="bi bi-controller"></i>
                <h3>Bộ từ luyện bằng game</h3>
                <p>Những bộ từ ngắn, nghĩa rõ, phù hợp để chơi Blocks, Blast hoặc Match trong vài phút.</p>
                <span>game · quick review</span>
            </article>
        </div>
    </section>

    <?php if ($publicLibraryEnabled && $latestSets): ?>
    <section class="latest-sets-section">
        <div class="section-heading centered">
            <span>Bộ từ public mới nhất</span>
            <h2>Khám phá nội dung do cộng đồng tạo ra</h2>
            <p>Những bộ từ được chia sẻ công khai gần đây. Đăng nhập để học ngay.</p>
        </div>
        <div class="latest-sets-grid">
            <?php foreach ($latestSets as $set): ?>
            <article class="latest-set-card">
                <span class="latest-set-level"><?= e($set['level'] ?? 'beginner') ?></span>
                <h3><?= e($set['title']) ?></h3>
                <p><?= e(mb_strlen($set['description'] ?? '') > 100 ? mb_substr($set['description'], 0, 100) . '...' : ($set['description'] ?? '')) ?></p>
                <div class="latest-set-meta">
                    <span><i class="bi bi-person"></i> <?= e($set['owner_name']) ?></span>
                    <span><i class="bi bi-card-list"></i> <?= (int) $set['card_count'] ?> thẻ</span>
                    <?php if (!empty($set['category_name'])): ?>
                    <span><i class="bi bi-folder"></i> <?= e($set['category_name']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($loggedIn): ?>
                <a class="btn btn-outline-primary btn-sm" href="<?= app_url('pages/library.php') ?>">Xem trong thư viện</a>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$loggedIn): ?>
        <div class="latest-sets-cta">
            <p>Đăng nhập để truy cập thư viện đầy đủ và bắt đầu học.</p>
            <a class="btn btn-primary" href="<?= app_url('login.php') ?>">Đăng nhập</a>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="sample-vocab-section">
        <div class="section-heading centered">
            <span>Thẻ mẫu</span>
            <h2>Một vài flashcard mẫu để bắt đầu học nhanh</h2>
        </div>
        <div class="sample-vocab-grid">
            <article class="sample-vocab-card">
                <strong>apple</strong>
                <span>/ˈæp.əl/</span>
                <p>quả táo</p>
            </article>
            <article class="sample-vocab-card">
                <strong>meeting</strong>
                <span>/ˈmiː.tɪŋ/</span>
                <p>cuộc họp</p>
            </article>
            <article class="sample-vocab-card">
                <strong>airport</strong>
                <span>/ˈer.pɔːrt/</span>
                <p>sân bay</p>
            </article>
        </div>
    </section>

    <section class="feature-depth-section">
        <div class="section-heading centered">
            <span>Chi tiết tính năng</span>
            <h2>Nhiều thao tác nhỏ được chuẩn bị để việc học mượt hơn</h2>
            <p>Không chỉ có thẻ từ, app còn chú ý đến nhập liệu, phát âm, quyền truy cập và tiến độ để người học quay lại dễ dàng.</p>
        </div>
        <div class="feature-depth-grid">
            <article><i class="bi bi-images"></i><strong>Ảnh minh họa</strong><span>Thêm hình vào thẻ để tạo liên kết thị giác khi học từ.</span></article>
            <article><i class="bi bi-soundwave"></i><strong>Ví dụ có âm thanh</strong><span>Đọc từ và câu ví dụ bằng SpeechSynthesis trong trình duyệt.</span></article>
            <article><i class="bi bi-arrow-repeat"></i><strong>Ôn câu sai</strong><span>Learn mode lưu câu sai để người học không bỏ sót phần yếu.</span></article>
            <article><i class="bi bi-search"></i><strong>Tìm bộ từ nhanh</strong><span>Thanh tìm kiếm giúp quay lại đúng bộ từ đang cần luyện.</span></article>
            <article><i class="bi bi-shield-check"></i><strong>Quyền riêng tư</strong><span>Chọn private, public, chia sẻ theo lớp hoặc theo từng người.</span></article>
            <article><i class="bi bi-bar-chart-line"></i><strong>Lịch sử học</strong><span>Lưu kết quả đúng sai, điểm test và thời điểm học gần nhất.</span></article>
            <article><i class="bi bi-phone"></i><strong>Dùng được trên mobile</strong><span>Layout tự co lại để học nhanh trên điện thoại hoặc máy tính bảng.</span></article>
            <article><i class="bi bi-palette"></i><strong>Sáng tối đồng bộ</strong><span>Theme được lưu lại để Home, Login và Dashboard cùng một trải nghiệm.</span></article>
        </div>
    </section>

    <section class="faq-section">
        <div class="section-heading centered">
            <span>Câu hỏi thường gặp</span>
            <h2>Người mới có thể bắt đầu rất nhanh</h2>
        </div>
        <div class="faq-grid">
            <article>
                <h3>Có mất phí không?</h3>
                <p>Không. Các chế độ học chính đều miễn phí trong ứng dụng.</p>
            </article>
            <article>
                <h3>Có cần dịch vụ đọc tiếng trả phí không?</h3>
                <p>Không. Phần đọc từ dùng Web Speech API có sẵn trên trình duyệt hỗ trợ.</p>
            </article>
            <article>
                <h3>Có thể dùng cho lớp học không?</h3>
                <p>Có. Bạn có thể tạo lớp, cấp quyền bộ từ cho lớp hoặc chia sẻ riêng cho từng người.</p>
            </article>
            <article>
                <h3>Có học lại câu sai không?</h3>
                <p>Có. Learn mode lưu câu sai và có tùy chọn học lại ngay hoặc tự động học lại.</p>
            </article>
            <article>
                <h3>Tôi có thể import từ vựng từ nơi khác không?</h3>
                <p>Có. Ứng dụng cung cấp công cụ import từ HTML (Quizlet, v.v.) thành bộ từ vựng có sẵn phiên âm IPA.</p>
            </article>
            <article>
                <h3>Dữ liệu của tôi có được sao lưu không?</h3>
                <p>Admin có thể sao lưu dữ liệu định kỳ qua trang Backup trong Admin Panel. Người dùng nên tự lưu nội dung quan trọng.</p>
            </article>
            <article>
                <h3>Có giới hạn số lượng bộ từ hoặc thẻ không?</h3>
                <p>Không có giới hạn cứng. Bạn có thể tạo bao nhiêu bộ từ và thẻ tùy thích, miễn là máy chủ còn tài nguyên.</p>
            </article>
            <article>
                <h3>Tôi có thể đổi giao diện sáng/tối không?</h3>
                <p>Có. Nút chuyển đổi Dark/Light mode có trên tất cả các trang và được lưu lại cho lần truy cập sau.</p>
            </article>
        </div>
    </section>

    <section class="founder-section" id="founder">
        <div class="founder-card">
            <div class="founder-profile">
                <div class="founder-avatar">K</div>
                <span>Founder & Product Builder</span>
                <h2>Trần Đăng Khoa</h2>
                <p>Người định hướng và phát triển <?= e(site_name()) ?> với mục tiêu tạo một không gian học từ vựng miễn phí, dễ dùng và đủ linh hoạt cho cá nhân, lớp học hoặc nhóm tự học.</p>
                <div class="founder-signature">
                    <i class="bi bi-lightbulb"></i>
                    <strong>Tập trung vào trải nghiệm học thực tế, tiến độ rõ ràng và khả năng chia sẻ kiến thức.</strong>
                </div>
            </div>
            <div class="founder-principles">
                <article>
                    <i class="bi bi-unlock"></i>
                    <h3>Miễn phí cho học tập cốt lõi</h3>
                    <p>Các chế độ học chính được xây dựng để người học có thể bắt đầu ngay mà không gặp rào cản.</p>
                </article>
                <article>
                    <i class="bi bi-bullseye"></i>
                    <h3>Ưu tiên ghi nhớ chủ động</h3>
                    <p>Flashcards, Learn, Test và các mini game đều hướng đến việc luyện nhớ bằng thao tác lặp lại có phản hồi.</p>
                </article>
                <article>
                    <i class="bi bi-person-check"></i>
                    <h3>Tôn trọng quyền sở hữu nội dung</h3>
                    <p>Người tạo bộ từ có thể giữ riêng tư, công khai, chia sẻ cho lớp hoặc cấp quyền cụ thể cho người khác.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="start-steps-section">
        <div class="section-heading centered">
            <span>Bắt đầu ngay</span>
            <h2>Chỉ 3 bước để bắt đầu hành trình học từ vựng</h2>
            <p>Không cần cài đặt gì thêm. Mọi thứ đều có sẵn trong trình duyệt.</p>
        </div>
        <div class="start-steps-grid">
            <article class="start-step-card">
                <span class="start-step-number">01</span>
                <i class="bi bi-person-plus-fill"></i>
                <h3>Đăng ký tài khoản</h3>
                <p>Tạo tài khoản miễn phí trong chưa đầy 1 phút. Không cần email xác minh, bắt đầu học ngay lập tức.</p>
            </article>
            <article class="start-step-card">
                <span class="start-step-number">02</span>
                <i class="bi bi-journal-bookmark-fill"></i>
                <h3>Chọn hoặc tạo bộ từ</h3>
                <p>Duyệt thư viện public để tìm bộ từ phù hợp, hoặc tự tạo bộ từ riêng với flashcard, phiên âm và câu ví dụ.</p>
            </article>
            <article class="start-step-card">
                <span class="start-step-number">03</span>
                <i class="bi bi-play-circle-fill"></i>
                <h3>Học với 6 chế độ</h3>
                <p>Dùng Flashcards, Learn, Test, Blocks, Blast hoặc Match để luyện tập theo cách bạn thích.</p>
            </article>
        </div>
    </section>

    <section class="home-cta">
        <h2>Sẵn sàng học thử?</h2>
        <p>Đăng nhập hoặc tạo tài khoản mới để trải nghiệm các chế độ học ngay.</p>
        <div class="hero-actions justify-content-center">
            <?php if ($loggedIn): ?>
                <a class="btn btn-primary btn-lg" href="<?= app_url('dashboard.php') ?>">Vào Dashboard</a>
            <?php else: ?>
                <a class="btn btn-primary btn-lg" href="<?= app_url('login.php') ?>">Đăng nhập</a>
                <?php if ($registrationEnabled): ?>
                    <a class="btn btn-outline-primary btn-lg" href="<?= app_url('register.php') ?>">Đăng ký</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="public-footer">
    <span>© <?= date('Y') ?> <?= e(get_setting('footer_text', site_name())) ?></span>
    <span>All rights reserved.</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
