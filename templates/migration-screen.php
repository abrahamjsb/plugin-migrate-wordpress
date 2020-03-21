<?php 

  $post_action = admin_url( 'admin-post.php' );

  if(isset($response)) {
  	var_dump($response);
  	exit;
  }

 ?>


<h1>Migration DB Primicias 24</h1>

<form action='<?=$post_action?>' method="post">
    <input type="hidden" name="action" value="start_migration">
    <button id="btn-activate-migration" type="submit">
	Activate migration
	</button>
 </form>


<div class="log-container">
	<div id="migration-loader" class="lds-hourglass" style="display: none;"></div>
</div>