<?php
require_once __DIR__ . '/config/config.php';

$loggedIn = !empty($_SESSION['user_id']);
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME ?> - Học tiếng Anh miễn phí</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="public-page home-page">
<nav class="public-nav">
    <a class="public-brand" href="<?= BASE_URL ?>">
        <span class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></span>
        <span><?= APP_NAME ?></span>
    </a>
    <div class="public-nav-links">
        <a href="#features">Tính năng</a>
        <a href="#modes">Chế độ học</a>
        <a href="#founder">Người sáng lập</a>
        <?php if ($loggedIn): ?>
            <a class="btn btn-primary" href="<?= app_url('dashboard.php') ?>">Vào Dashboard</a>
        <?php else: ?>
            <a href="<?= app_url('login.php') ?>">Đăng nhập</a>
            <a class="btn btn-primary" href="<?= app_url('register.php') ?>">Bắt đầu miễn phí</a>
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
                    <a class="btn btn-primary btn-lg" href="<?= app_url('register.php') ?>">Tạo tài khoản miễn phí</a>
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
        </div>
    </section>

    <section class="founder-section" id="founder">
        <div class="founder-card">
            <div class="founder-profile">
                <div class="founder-avatar">K</div>
                <span>Founder & Product Builder</span>
                <h2>Trần Đăng Khoa</h2>
                <p>Người định hướng và phát triển <?= APP_NAME ?> với mục tiêu tạo một không gian học từ vựng miễn phí, dễ dùng và đủ linh hoạt cho cá nhân, lớp học hoặc nhóm tự học.</p>
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

    <section class="home-cta">
        <h2>Sẵn sàng học thử?</h2>
        <p>Đăng nhập hoặc tạo tài khoản mới để trải nghiệm các chế độ học ngay.</p>
        <div class="hero-actions justify-content-center">
            <?php if ($loggedIn): ?>
                <a class="btn btn-primary btn-lg" href="<?= app_url('dashboard.php') ?>">Vào Dashboard</a>
            <?php else: ?>
                <a class="btn btn-primary btn-lg" href="<?= app_url('login.php') ?>">Đăng nhập</a>
                <a class="btn btn-outline-primary btn-lg" href="<?= app_url('register.php') ?>">Đăng ký</a>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="public-footer">
    <span>© <?= date('Y') ?> <?= APP_NAME ?>. Founded and developed by Trần Đăng Khoa.</span>
    <span>All rights reserved.</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
