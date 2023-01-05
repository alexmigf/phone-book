<?php
do_action( 'phone_book_before_contacts_list', $this );
$contact_list->prepare_items();
$contact_list->views();
?>
<form id="<?= Phone_Book()->slug; ?>-contacts-search-form" method="get">
	<input type="hidden" name="post_type" value="<?= $post_type; ?>" />
	<input type="hidden" name="page" value="<?= esc_attr( $_REQUEST['page'] ); ?>" />
	<input type="hidden" name="tab" value="contacts" />
	<?php $contact_list->search_box( __( 'Search', Phone_Book()->slug ), Phone_Book()->slug ); ?>
</form>
<form id="<?= Phone_Book()->slug; ?>-contacts-form" method="post">
	<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
	<?php $contact_list->display(); ?>
</form>
<?php
do_action( 'phone_book_after_contacts_list', $this );