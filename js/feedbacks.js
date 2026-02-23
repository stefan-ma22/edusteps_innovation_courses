var innovationGroups = [];

jQuery(document).ready(function () {

    if (window.location.search) {
        const urlParams = new URLSearchParams(window.location.search);
        const group = urlParams.get('group');
        if (group) {
            loadFeedbacks(group);
        }
    }

    jQuery("#generate_feedback_form").on("click", function () {
        // get form_post_id from url parameter
        const urlParams = new URLSearchParams(window.location.search);
        const form_post_id = urlParams.get('form_post_id');
        generateFeedbackLink(form_post_id);
    });

    jQuery("#generate_feedback_form_for_specific_group").on("click", function () {
        generateFeedbackLinkForSpecificGroup(jQuery(this).data('group'));
    });

    jQuery("#innovationTopic").on("change", function () {
        var selectedProgram = jQuery("#innovationTopic").val();
        var year = jQuery("#innovationYears");
        //clear year
        year.html('<option value="">Vyberte rok</option>');
        if (selectedProgram) {
            var years = innovationGroups[selectedProgram];
            for (var singleYear in years) {
                year.append('<option value="' + singleYear + '">' + singleYear + '</option>');
            }
        }
    });

    jQuery("#innovationYears").on("change", function () {
        var selectedProgram = jQuery("#innovationTopic").val();
        var selectedYear = jQuery(this).val();
        var groups = innovationGroups[selectedProgram][selectedYear];
        var groupSelect = jQuery("#innovationGroups");
        groupSelect.html('<option value="">Vyberte skupinu</option>');
        for (var singleGroup in groups) {
            groupSelect.append('<option value="' + groups[singleGroup] + '">' + groups[singleGroup] + '</option>');
        }
    });

    jQuery(document).on('change', '#innovationGroups', function () {
        var selectedGroup = jQuery(this).val();
        //add get parameter to url
        var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?group=' + encodeURIComponent(selectedGroup);
        window.history.replaceState({ path: newUrl }, '', newUrl);
        loadFeedbacks(selectedGroup);
    });
});

function generateFeedbackLink(form_post_id) {
    // open modal and load all innovation groups and after user selects a group, generate link in the modal and add button which will copy to clipboard
    var modalBody = '<p>Vyberte skupinu pre ktorú chcete vygenerovať odkaz na spätnú väzbu:</p>';
    modalBody += '<select id="feedbackGroupSelect">';
    // Load all innovation groups
    loadInnovationGroups(form_post_id).then(groups => {
        groups.forEach(function (group) {
            modalBody += '<option value="' + group.group_name + '">' + group.group_name + '</option>';
        });
        modalBody += '</select>';
        modalBody += '<button style="margin-left: 10px;" onClick="generateFeedbackLinkForSpecificGroup(jQuery(\'#feedbackGroupSelect\').val())">Vygenerovať odkaz</button>';
        jQuery('.modal-body').html(modalBody);
        jQuery('#actionConfirmTitle').html('Vyberte skupinu pre spätnú väzbu');
        hideLoading();

        showModal();
    }).catch(e => {
        console.log(e.responseJSON);
        showResultOnFail('', 'Nepodarilo sa načítať skupiny. Dôvod: ' + e.responseJSON.data);
    });
}

function generateFeedbackLinkForSpecificGroup(group) {
    var url = window.location.origin + '/inovacne-vzdelavanie-spatna-vazba/?group=' + encodeURIComponent(group);
    //copy to clipboard
    navigator.clipboard.writeText(url).then(function () {
        showResultOnSuccess('Odkaz bol skopírovaný do schránky', url);
    }, function (err) {
        console.error('Could not copy text: ', err);
        showResultOnFail('', 'Nepodarilo sa skopírovať odkaz do schránky. Skúste to prosím ručne: ' + url);
    });
}

function loadInnovationGroups(form_post_id) {
    var formData = new FormData();
    formData.append("method", "getInnovationGroups");
    formData.append("form_post_id", form_post_id);
    return callBackend(formData).then((response) => {
        return response.data;
    });
}

function prepareInnovationGroups() {
    return loadInnovationGroups(null).then(groups => {
            groups.forEach(function (group) {
                if (!innovationGroups[group.program]) {
                    innovationGroups[group.program] = [];
                }

                if (!innovationGroups[group.program][group.year]) {
                    innovationGroups[group.program][group.year] = [];
                }

                innovationGroups[group.program][group.year].push(group.group_name);
            });
            hideLoading();
        }).catch(e => {
            console.log(e);
            showResultOnFail('', 'Nepodarilo se načíst skupiny. Důvod: ' + e.responseJSON.data);
        });
}

function showInnovationGroupsInFeedbackStatistics() {
    prepareInnovationGroups().then(() => {
        var select = jQuery("#innovationTopic");
        for (var i in innovationGroups) {
            var program = i;
            select.append('<option value="' + program + '">' + program + '</option>');
        };
    });
}

function loadFeedbacks(selectedGroup) {

    var formData = new FormData();
    formData.append("method", "loadFeedbacks");
    if (selectedGroup) {
        formData.append("selectedInnovationGroup", selectedGroup);
    } else {
        return;
    }
    callBackend(formData).then((response) => {
        hideLoading();
        jQuery("#group").text(selectedGroup);
        showFeedbackCharts(response);
    });
}

function showFeedbackCharts(answers) {
    const container = document.getElementById('feedbacks_container');
    container.innerHTML = ''; // Clear previous charts

    for (const [index, questionDetails] of Object.entries(answers)) {
        const chartWrapper = document.createElement('div');
        chartWrapper.style.marginBottom = '50px';

        const header = document.createElement('h4');
        header.textContent = questionDetails.question;
        chartWrapper.appendChild(header);

        if (questionDetails.answersType === 'radio' || questionDetails.answersType === 'checkbox') {
            const canvas = document.createElement('canvas');
            chartWrapper.appendChild(canvas);

            container.appendChild(chartWrapper);
            createChart(canvas, questionDetails.selectedAnswers);
        } else {
            const list = document.createElement('ul');
            list.style.listStyleType = 'none';
            for (const [key, answer] of Object.entries(questionDetails.writtenAnswers)) {
                const listItem = document.createElement('li');
                listItem.textContent = answer;
                listItem.style.marginLeft = '20px';
                listItem.style.background = 'rgba(60, 60, 60, 0.1)';
                listItem.style.padding = '5px';
                listItem.style.margin = '5px';
                listItem.style.borderRadius = '5px';

                list.appendChild(listItem);
            }
            chartWrapper.appendChild(list);
            container.appendChild(chartWrapper);
        }
    }
}

function createChart(canvas, answers) {
    const ctx = canvas.getContext('2d');
    //set height and width of the canvas
    ctx.canvas.height = 400;
    ctx.canvas.width = 800;

    var labels;

    //if first is a number
    if (!isNaN(parseInt(Object.keys(answers)[0]))) {
        for (var i = 0; i < 6; i++) {
            if (!answers[i]) {
                answers[i] = 0;
            }
        }
        labels = Object.keys(answers).map((key, i) => parseInt(key));
    } else {
        labels = Object.keys(answers);
    }

    const total = Object.values(answers).reduce((a, b) => a + b, 0);

    Chart.register(ChartDataLabels);
    new Chart(ctx, {
        type: 'bar',
        showLines: true,
        data: {
            labels: labels || Object.keys(answers),
            datasets: [{
                label: 'Počet odpovedí',
                data: Object.values(answers),
                backgroundColor: 'rgba(60, 60, 60, 1)', // Dark grey bar
            }]
        },
        options: {
            responsive: false,
            plugins: {
                datalabels: {
                    anchor: 'center',
                    align: 'center',
                    color: 'white',
                    formatter: (value, context) => {
                        // Calculate raw percentages
                        const rawPercentages = Object.keys(answers).map((key, i) => (answers[key] / total) * 100);
                        return rawPercentages[context.dataIndex].toFixed(2) + "%";
                    },
                    font: {
                        weight: 'bold'
                    }
                },
                legend: { display: false },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}