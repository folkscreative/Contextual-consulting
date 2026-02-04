<div class="wrap">
    <h2>Workshops Plugin Template</h2>
    <form method="post" action="options.php"> 
        <?php @settings_fields('workshops_plugin-group'); ?>
        <?php @do_settings_fields('workshops_plugin-group'); ?>

        <?php do_settings_sections('workshops_plugin'); ?>

        <?php @submit_button(); ?>
    </form>
</div>