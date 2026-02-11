<?php
//switch between deleted and active topics
$visibility_button_name = "";
if (isset($_GET['is_visible'])) {
    $switch_to_url = admin_url('admin.php?page=iv-topics-edit');
    $visibility_button_name = 'Zobraziť aktívne témy';
    $is_visible = 0;
    $delete_restore_topic_button = "restore_iv_topic";
    $delete_restore_topic_button_name = "Obnoviť tému";
} else {
    $switch_to_url = admin_url('admin.php?page=iv-topics-edit&is_visible=0');
    $visibility_button_name = 'Zobraziť vymazané témy';
    $is_visible = 1;
    $delete_restore_topic_button = "delete_iv_topic";
    $delete_restore_topic_button_name = "Vymazať tému";
}

global $wpdb;
//get prefix
$iv_topics = $wpdb->prefix . 'iv_topics';
$topics = $wpdb->get_results("SELECT * FROM {$iv_topics} WHERE is_visible = {$is_visible}");

$iv_topics_emails = $wpdb->prefix . 'iv_topics_material_emails';

?>
<br>
<h3>Zoznam tém a materiálov inovačného vzdelávania</h3>
<button type="button" class="btn btn-secondary" id="add_new_iv_topic">Pridať novú tému</button>
<a href="<?php echo $switch_to_url ?>" class="btn btn-secondary" id="add_new_iv_topic" style="margin-left: 50px;"><?php echo $visibility_button_name; ?></a>
<div class="wrap">
    <table class="widefat">
        <thead>
            <tr>
                <th style="position: sticky;top: 32px; background-color:#efeeee;border-bottom:1px solid black;" scope="col">Téma</th>
                <th style="position: sticky;top: 32px; background-color:#efeeee;border-bottom:1px solid black;" scope="col">Akcia</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topics as $topic) { ?>
                <tr style="border-top:1px solid black">
                    <td><h5><?php echo $topic->topic_name; ?></h5></td>
                    <td>
                        <button type="button" data-id="<?php echo $topic->ID; ?>" class="btn btn-secondary edit_iv_topic">Upraviť tému</button>
                        <button type="button" data-id="<?php echo $topic->ID; ?>" class="btn btn-secondary <?php echo $delete_restore_topic_button; ?>" style="margin-left:20px;"><?php echo $delete_restore_topic_button_name; ?></button>
                    </td>
                </tr>
                <tr>
                    <td colspan="5">
                        <b>Úvodný email a materiály</b>
                        <div class="topic_files">
                            <?php
                                $emails = $wpdb->get_results("SELECT * FROM {$iv_topics_emails} WHERE is_visible = {$is_visible} AND iv_topic_id = {$topic->ID} ORDER BY `order` ASC", ARRAY_A);
                                foreach ($emails as $email) {
                                    $email_order = $email['order'];
                                    if ($email_order == 0) {
                                        $email_order = "Úvodný email - " . ($email['learning_mode'] == 0 ? "samoštúdium" : "s lektormi");
                                    } elseif ($email_order == 5) {
                                        $email_order = "Záverečná prezentácia a obhajoba - "  . ($email['learning_mode'] == 0 ? "samoštúdium" : "s lektormi");
                                    } else {
                                        $email_order = $email_order . ". časť - "  . ($email['learning_mode'] == 0 ? "samoštúdium" : "s lektormi");
                                    }
                                    echo '<p style="margin-bottom:2px;">' . $email_order . '
                                        <button type="button" data-id="' . $email['ID'] . '" class="btn-small btn-secondary edit_iv_material_email">Upraviť email</button>
                                        <button type="button" style="margin-left:10px;" data-id="' . $email['ID'] . '" class="btn-small btn-secondary delete_iv_materialEmail">Zmazať email</button></p>';
                                }

                            //add new program button
                            echo '<button type="button" data-id="' . $topic->ID . '" class="btn-small btn-secondary add_new_iv_materialEmail" style="margin-top:10px;">Pridať nový email</button>';
                            ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php wp_enqueue_media(); ?>