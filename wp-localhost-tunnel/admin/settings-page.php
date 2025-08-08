<div class="wrap">
    <h2>WP Localhost Tunnel</h2>
    <p>This plugin creates a tunnel from your local WordPress site to the public internet using your own server.</p>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="wplt_start_tunnel">
        <button type="submit" class="button button-primary">Start Tunnel</button>
    </form>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top:10px;">
        <input type="hidden" name="action" value="wplt_stop_tunnel">
        <button type="submit" class="button button-secondary">Stop Tunnel</button>
    </form>

    <?php if (isset($_GET['started'])): ?>
        <div style="margin-top:20px;">
            <strong>Tunnel Started!</strong><br>
            Public URL: <a href="<?php echo esc_url(WPLT_PUBLIC_URL); ?>" target="_blank"><?php echo esc_html(WPLT_PUBLIC_URL); ?></a>
        </div>
    <?php elseif (isset($_GET['stopped'])): ?>
        <div style="margin-top:20px;"><strong>Tunnel Stopped.</strong></div>
    <?php endif; ?>
</div>
