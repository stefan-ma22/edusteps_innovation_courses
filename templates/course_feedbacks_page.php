<?php
wp_enqueue_script("chart-js", "https://cdn.jsdelivr.net/npm/chart.js", array(), null, true);
wp_enqueue_script("chartjs-datalabels", "https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2", array("chart-js"), null, true);

wp_enqueue_script("edusteps-feedbacks", plugin_dir_url(__FILE__) . "../js/feedbacks.js?" . time(), array("jquery"), null, true);
?>

<h2 style="text-align:center !important">Hodnotenia inovačného vzdelávania</h2>
<div style="display: flex; justify-content: center; align-items: center; margin-bottom: 20px;">
    <table>
        <tr>
            <td><label for="innovationTopic" style="font-size: 18px">
                    Vyberte vzdelávanie:
            </td>
            <td>
                <select id="innovationTopic" style="margin-left: 5px;margin-bottom: 0.5rem;max-width: 300px;">
                    <option value="" disabled selected>Vyberte vzdelávanie</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label for="innovationYears" style="font-size: 18px">
                    Vyberte rok:
            </td>
            <td>
                <select id="innovationYears" style="margin-left: 5px;margin-bottom: 0.5rem;">
                    <option value="" disabled selected>Vyberte rok</option>
                </select></br>
            </td>
        </tr>
        <tr>
            <td>
                <label for="innovationGroups" style="font-size: 18px">
                    Vyberte skupinu:
            </td>
            <td>
                <select id="innovationGroups" style="margin-left: 5px;margin-bottom: 0.5rem;">
                    <option value="" disabled selected>Vyberte skupinu</option>
                </select>
            </td>
        </tr>
    </table>
</div>
<h3 style="text-align:center !important" id="group"></h3>
<div class="col-md-12"
    style="margin-left:auto; margin-right: auto;display: flex; justify-content: center; align-items: flex-start;max-width: 1200px;">
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