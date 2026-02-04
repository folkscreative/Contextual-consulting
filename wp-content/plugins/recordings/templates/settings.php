<div class="wrap">
    <h2>Recordings Plugin Template</h2>
    <form method="post" action="options.php"> 
        <?php @settings_fields('recordings_plugin-group'); ?>
        <?php @do_settings_fields('recordings_plugin-group'); ?>

        <?php do_settings_sections('recordings_plugin'); ?>

        <?php @submit_button(); ?>
    </form>
</div>