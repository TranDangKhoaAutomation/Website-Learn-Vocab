        <?php
        require_once __DIR__ . '/settings.php';
        $footerText = trim((string) get_setting('footer_text', site_name()));
        $footerBrand = $footerText !== '' ? $footerText : site_name();
        $contactEmail = trim((string) get_setting('contact_email', ''));
        ?>
        <footer class="app-footer">
            <span>© <?= date('Y') ?> <?= e($footerBrand) ?>. All rights reserved.</span>
            <?php if ($contactEmail !== ''): ?>
                <span><?= e($contactEmail) ?></span>
            <?php endif; ?>
        </footer>
    </div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
