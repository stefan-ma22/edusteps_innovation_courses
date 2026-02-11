<?php
wp_enqueue_script("chart-js", "https://cdn.jsdelivr.net/npm/chart.js", array(), null, true);
wp_enqueue_script("chartjs-datalabels", "https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2", array("chart-js"), null, true);

wp_enqueue_script("edusteps-feedbacks", plugin_dir_url(__FILE__) . "../js/feedbacks.js", array("jquery"), null, true);
?>

<div id="navigation_buttons">
    <div><a href="<?php echo explode("?", get_current_url())[0]; ?>" class="btn btn-primary">Späť na hlavnú stránku</a></div>
    <div><a href="<?php echo explode("?", get_current_url())[0]; ?>?area=registrations_management" class="btn btn-primary">Všetky registrácie</a></div>
    <div><a href="<?php echo explode("?", get_current_url())[0]; ?>?area=notifications_management" class="btn btn-primary">Odoslať linky a certifikáty</a></div>
    <div><a href="#a" id="generate_feedback_form" class="btn btn-primary">Vygenerovať formulár pre skupinu</a></div>
</div>

<h2 style="text-align:center !important">Hodnotenia inovačného vzdelávania</h2>
<div class="col-md-12" style="margin-left:auto; margin-right: auto;display: flex; justify-content: center; align-items: flex-start;max-width: 1200px;">
    <div class="col-md-3" style="min-width: 200px;">
        <h3 style="text-align: center;">Skupiny</h3>
        <div style="justify-content: center; align-items: center;" class="row">
            <ul id="innovationGroups">
            </ul>
        </div>
    </div>
    <div class="col-md-9" style="min-width: 500px;" id="feedbacks_container">
    </div>
</div>

<?php
include(WP_PLUGIN_DIR . "/edusteps_courses_automation/templates/alertsAndLoadings.php");

?>

<script>
    window.addEventListener('load', function () {
        showInnovationGroupsInFeedbackStatistics();
        loadFeedbacks();
    });
</script>