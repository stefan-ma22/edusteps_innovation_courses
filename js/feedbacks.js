jQuery(document).ready(function () {
    jQuery("#generate_feedback_form").on("click", function () {
        generateFeedbackLink();
    });

    jQuery(document).on('change', '.innovation-group', function () {
        loadFeedbacks();
    });
});

function generateFeedbackLink() {
    // open modal and load all innovation groups and after user selects a group, generate link in the modal and add button which will copy to clipboard
    var modalBody = '<p>Vyberte skupinu pre ktorú chcete vygenerovať odkaz na spätnú väzbu:</p>';
    modalBody += '<select id="feedbackGroupSelect">';
    // Load all innovation groups
    loadInnovationGroups().then(groups => {
        groups.forEach(function (group) {
            modalBody += '<option value="' + group.group_name + '">' + group.group_name + '</option>';
        });
        modalBody += '</select>';
        modalBody += '<button style="margin-left: 10px;" id="generateFeedbackLinkButton">Vygenerovať odkaz</button>';
        jQuery('.modal-body').html(modalBody);
        jQuery('#actionConfirmTitle').html('Vyberte skupinu pre spätnú väzbu');
        showModal();
        hideLoading();

        // Add event listener for the button
        jQuery("#generateFeedbackLinkButton").on("click", function () {
            //copy to clipboard the link
            //get url without parameters
            var selectedGroup = jQuery("#feedbackGroupSelect").val();
            var url = window.location.origin + '/inovacne-vzdelavanie-spatna-vazba/?group=' + encodeURIComponent(selectedGroup);
            navigator.clipboard.writeText(url).then(function () {
                showResultOnSuccess('Odkaz bol skopírovaný do schránky');
            }, function (err) {
                showResultOnFail('', 'Nepodarilo sa skopírovať odkaz do schránky');
            });
        });
    }).catch(e => {
        console.log(e.responseJSON);
        showResultOnFail('', 'Nepodarilo sa načítať skupiny. Dôvod: ' + e.responseJSON.data);
    });
}

function loadInnovationGroups() {
    var formData = new FormData();
    formData.append("method", "getInnovationGroups");
    return callBackend(formData).then((response) => {
        return response.data;
    });
}

function showInnovationGroupsInFeedbackStatistics() {
    loadInnovationGroups().then(groups => {
        var groupList = jQuery("#innovationGroups");
        groupList.empty();
        groups.forEach(function (group) {
            groupList.append('<li><label><input type="radio" name="group" class="innovation-group" value="' + group.group_name + '"> ' + group.group_name + '</label></li>');
        });
        hideLoading();
    });
}

function loadFeedbacks() {
    //check also innovatioNGroups selected, if non, send all
    var formData = new FormData();
    var selectedGroup = "";
    jQuery('input[name="group"]:checked').each(function () {
        selectedGroup = jQuery(this).val();
    });

    if (selectedGroup) {
        formData.append("selectedInnovationGroup", selectedGroup);
    } else {
        return;
    }

    formData.append("method", "loadFeedbacks");
    callBackend(formData).then((response) => {
        hideLoading();
        console.log(response);

        showFeedbackCharts(response);
    });
}

function showFeedbackCharts(answers) {
    const container = document.getElementById('feedbacks_container');
    container.innerHTML = ''; // Clear previous charts

    for (const [index, questionDetails] of Object.entries(answers)) {
        const chartWrapper = document.createElement('div');
        chartWrapper.style.marginBottom = '50px';

        const header = document.createElement('h3');
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
                listItem.style.height = '40px';
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