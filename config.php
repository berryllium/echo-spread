<div class="wrap">
    <h2>Echo spread API</h2>
    <form method="post" action="options.php">
        <?php
        settings_fields('echo_spread_settings');
        do_settings_sections('echo_spread');
        submit_button();
        ?>
    </form>
</div>