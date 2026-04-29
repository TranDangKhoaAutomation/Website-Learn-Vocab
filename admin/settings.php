<?php
require_once __DIR__ . '/_admin.php';
$defs = [
['site_name','Tên website','text'],['site_tagline','Tagline','text'],['registration_enabled','Bật đăng ký','boolean'],['public_set_moderation','Duyệt bộ từ public','boolean'],['maintenance_mode','Maintenance','boolean'],['default_test_questions','Số câu test mặc định','number'],['blast_default_seconds','Thời gian Blast','number'],['contact_email','Email liên hệ','text'],['footer_text','Footer','text'],['leaderboard_enabled','Leaderboard','boolean'],['feedback_enabled','Feedback','boolean'],['public_library_enabled','Public library','boolean']
];
if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); foreach($defs as [$k,$label,$type]) { $value = $type==='boolean' ? isset($_POST[$k]) : ($_POST[$k] ?? ''); set_setting($pdo,$k,$value,$type); } audit_log($pdo,'settings_update','settings',null,null,$_POST); set_flash('success','Đã lưu settings.'); redirect('admin/settings.php'); }
admin_header('Settings', 'settings');
?>
<section class="panel"><form method="post" class="stack-form"><?= csrf_field() ?><div class="row g-3"><?php foreach($defs as [$k,$label,$type]): ?><div class="col-md-6"><label class="form-label"><?= e($label) ?></label><?php if($type==='boolean'): ?><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="<?= e($k) ?>" <?= get_setting($k,false)?'checked':'' ?>></div><?php else: ?><input class="form-control" name="<?= e($k) ?>" value="<?= e(get_setting($k,'')) ?>" type="<?= $type==='number'?'number':'text' ?>"><?php endif; ?></div><?php endforeach; ?></div><button class="btn btn-primary mt-3">Lưu cài đặt</button></form></section>
<?php admin_footer(); ?>
