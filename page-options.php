<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2>Backup Settings</h2>
  <p>This page will end up being the options page for the plugin, but until then there is only this paragraph</p>
  <form method="post">
    <?php wp_nonce_field( 'create_backup' ); ?>
    <input type='submit' name='create_backup' value='Backup Database'/>
  </form>
</div>