jQuery("#add_new_iv_topic").on("click", function () {
    jQuery('.modal-body').html('');
    jQuery('.modal-title').html('Pridať novú tému');
    var modalBody = getIvTopicForm();
    jQuery('.modal-body').html(modalBody);
    jQuery('#submitAction').attr('onClick', 'addNewIvTopic();');
    jQuery('#submitAction').text('Pridať tému');
    showModal();
});

function addNewIvTopic() {
    //add empty checks
    if (jQuery('#topic_name').val() == '') {
        showResultOnFail('', 'Vyplňte názov témy.');
        return;
    }
    var formData = new FormData();
    formData.append("method", "addNewIvTopic");
    formData.append("topic_name", jQuery('#topic_name').val());

    callBackend(formData).then((response) => {
        showSuccessMessage("Téma bola úspešne pridaná.");
        setTimeout(() => {
            window.location.reload();
        }, 2000);
        location.reload();
    }).catch(e => {
        showResultOnFail('', 'Nepodarilo sa vytvoriť novú tému. Skúste znovu alebo kontaktujte admina.');
        console.log(e);
    });
}

function getIvTopicForm() {
    var modalBody = '<form id="addNewTopicForm">';
    modalBody += '<input type="hidden" id="iv_topic_id" name="iv_topic_id" value="">'
    modalBody += '<div class="form-group">';
    modalBody += '<label for="topic_name">Názov témy</label>';
    modalBody += '<input type="text" class="form-control" id="topic_name" name="topic_name" placeholder="Názov témy">';
    modalBody += '</div>';
    modalBody += "</form>";
    return modalBody;
}

function getIvMaterialEmailForm() {
    var modalBody = '<form id="addNewIvMaterialEmailForm">';
    modalBody += '<input type="hidden" id="iv_materialEmail_id" name="iv_materialEmail_id" value="">'
    modalBody += '<input type="hidden" id="iv_topic_id" name="iv_topic_id" value="">'
    modalBody += '<div class="form-group">';
    modalBody += '<label for="materialEmail_order">Poradie odosielania</label>';
    modalBody += '<select id="materialEmail_order" name="materialEmail_order" style="margin-left: 10px;margin-top: -10px;">';
    modalBody += '<option>Vyberte</option>';
    modalBody += '<option value="0">Úvodný email</option>';
    modalBody += '<option value="1">Materiály - 1. časť</option>';
    modalBody += '<option value="2">Materiály - 2. časť</option>';
    modalBody += '<option value="3">Materiály - 3. časť</option>';
    modalBody += '<option value="4">Materiály - 4. časť</option>';
    modalBody += '<option value="5">Záverečná prezentácia a obhajoba</option>';
    modalBody += '</select>';
    modalBody += '</div>';
    modalBody += '<div class="form-group">';
    modalBody += '<label for="learning_mode">Typ vzdelávania</label>';
    modalBody += '<select id="learning_mode" name="learning_mode" style="margin-left: 10px;margin-top: -10px;">';
    modalBody += '<option>Vyberte</option>';
    modalBody += '<option value="0">Samoštúdium</option>';
    modalBody += '<option value="1">S lektormi</option>';
    modalBody += '</select>';
    modalBody += '</div>';
    modalBody += '<div class="form-group">';
    //add tinymce editor
    modalBody += '<label for="document_body">Obsah emailu</label>';
    modalBody += '<textarea id="document_body" name="document_body"></textarea>';
    modalBody += '<input type="button" name="emailAttachment" id="iv_email_attachment" class="button-primary" value="Vybrať prílohu / Pridať obrázok">';
    modalBody += '</div>';
    modalBody += "</form>";
    return modalBody;
}


jQuery(".edit_iv_topic").on("click", async function () {
    jQuery('.modal-body').html('');
    jQuery('.modal-title').html('Upraviť tému');
    var modalBody = getIvTopicForm();
    jQuery('.modal-body').html(modalBody);
    //add data to form
    var formData = new FormData();
    formData.append("method", "getSingleIvTopic");
    formData.append("iv_topic_id", jQuery(this).attr('data-id'));
    callBackend(formData).then((response) => {
        jQuery('#topic_name').val(response.data.topic_name);
        jQuery("#iv_topic_id").val(response.data.ID);
        jQuery('#learning_mode').val(response.data.learning_mode);
        jQuery('#submitAction').attr('onClick', 'updateIvTopic();');
        jQuery('#submitAction').text('Upraviť tému');
        showModal();
        hideLoading();
    }).catch(e => {
        showResultOnFail('', 'Nepodarilo sa načítať tému. Skúste znovu alebo kontaktujte admina.');
        console.log(e);
    });
});

jQuery(".edit_iv_material_email").on("click", function () {
    jQuery('.modal-body').html('');
    var modalBody = getIvMaterialEmailForm();
    jQuery('.modal-body').html(modalBody);
    jQuery('.modal-dialog').css('min-width', '50%');
    var materialEmailTitle = jQuery(this).closest('tr').prev('tr').find('td').eq(0).text();
    //add data to form
    var formData = new FormData();
    formData.append("method", "fetchSingleIvMaterialEmail");
    formData.append("id", jQuery(this).attr('data-id'));
    callBackend(formData).then((response) => {
        jQuery('#materialEmail_order').val(response.data.order);
        jQuery("#iv_materialEmail_id").val(response.data.ID);
        jQuery('#learning_mode').val(response.data.learning_mode);
        showModal();
        initializeTinyMCE('#document_body', response.data.email);

        jQuery('#submitAction').attr('onClick', 'updateIvMaterialEmail();');
        jQuery('#submitAction').text('Upraviť program');
        jQuery('.modal-title').html('Upraviť program - ' + materialEmailTitle);
        hideLoading();
    }).catch(e => {
        showResultOnFail('', 'Nepodarilo sa upraviť dokument. Skúste znovu alebo kontaktujte admina.');
        console.log(e);
    });
});

jQuery(".delete_iv_material_email").on("click", function () {
    if (confirm("Naozaj chcete vymazať tento program?")) {
        var formData = new FormData();
        formData.append("method", "deleteIvMaterialEmail");
        formData.append("id", jQuery(this).attr('data-id'));
        callBackend(formData).then((response) => {
            showSuccessMessage("Program bol vymazaný.");
            jQuery(this).closest('p').remove();
        }).catch(e => {
            showResultOnFail('', 'Nepodarilo sa vymazať program. Skúste znovu alebo kontaktujte admina.');
            console.log(e);
        });
    }
});

function updateIvTopic() {
    //add empty checks
    if (jQuery('#topic_name').val() == '') {
        showResultOnFail('', 'Vyplňte názov témy.');
        return;
    }
    var formData = new FormData();
    formData.append("method", "updateIvTopic");
    formData.append("iv_topic_id", jQuery("#iv_topic_id").val());
    formData.append("topic_name", jQuery('#topic_name').val());

    callBackend(formData).then((response) => {
        showSuccessMessage("Téma bola upravená.");
        location.reload();
    }).catch(e => {
        showResultOnFail('', 'Nepodarilo sa upraviť tému. Skúste znovu alebo kontaktujte admina.');
        console.log(e);
    });
}

function updateIvMaterialEmail() {
    //add empty checks
    if (jQuery('#materialEmail_order').val() == '' || jQuery('#learning_mode').val() == '' || tinymce.get("document_body").getContent() == '') {
        showResultOnFail('', 'Vyplňte všetky polia.');
        return;
    }

    var formData = new FormData();
    formData.append("method", "updateIvMaterialEmail");
    formData.append("iv_materialEmail_id", jQuery("#iv_materialEmail_id").val());
    formData.append("materialEmail_order", jQuery('#materialEmail_order').val());
    formData.append("materialEmail_body", tinymce.get("document_body").getContent());
    formData.append("learning_mode", jQuery('#learning_mode').val());
    callBackend(formData).then((response) => {
        showSuccessMessage("Email bol upravený.");
    }).catch(e => {
        showResultOnFail('', 'Nepodarilo sa upraviť email. Skúste znovu alebo kontaktujte admina.');
        console.log(e);
    });

}

jQuery(".add_new_iv_materialEmail").on("click", function () {
    jQuery('.modal-body').html('');
    topic_name = jQuery(this).closest('tr').prev().find('h5').eq(0).text();
    jQuery('.modal-title').html('Pridať nový email - ' + topic_name);
    var modalBody = getIvMaterialEmailForm();
    jQuery('.modal-body').html(modalBody);
    jQuery('#iv_topic_id').val(jQuery(this).attr('data-id'));
    jQuery('.modal-dialog').css('min-width', '50%');
    jQuery('#submitAction').attr('onClick', 'addNewIvMaterialEmail();');
    jQuery('#submitAction').text('Pridať email');
    initializeTinyMCE('#document_body');
    showModal();
});

function addNewIvMaterialEmail() {
    //add empty checks
    if (jQuery('#materialEmail_order').val() == '' || jQuery('#learning_mode').val() == '' || tinymce.get("document_body").getContent() == '') {
        showResultOnFail('', 'Vyplňte všetky polia.');
        return;
    }
    var formData = new FormData();
    formData.append("method", "addNewIvMaterialEmail");
    formData.append("materialEmail_order", jQuery('#materialEmail_order').val());
    formData.append("materialEmail_body", tinymce.get("document_body").getContent());
    formData.append("learning_mode", jQuery('#learning_mode').val());
    formData.append("topic_id", jQuery("#iv_topic_id").val());
    callBackend(formData).then((response) => {
        showSuccessMessage("Email bol pridaný.");
        setTimeout(() => {
            window.location.reload();
        }, 2000);
        location.reload();
    }).catch(e => {
        showResultOnFail('', 'Nepodarilo sa pridať email. Skúste znovu alebo kontaktujte admina.');
        console.log(e);
    });
}

jQuery(".delete_iv_topic").on("click", function () {
    if (confirm("Naozaj chcete zmazať tému?") == false) {
        return;
    }
    var formData = new FormData();
    formData.append("method", "deleteIvTopic");
    formData.append("iv_topic_id", jQuery(this).attr('data-id'));
    callBackend(formData).then((response) => {
        showSuccessMessage("Téma bola úspešne zmazaná.");
        setTimeout(() => {
            window.location.reload();
        }, 2000);
        location.reload();
    }).catch(e => {
        showResultOnFail('', 'Nepodarilo sa zmazať tému. Skúste znovu alebo kontaktujte admina.');
        console.log(e);
    });
});

//restore_iv_topic
jQuery(".restore_iv_topic").on("click", function () {
    if (confirm("Naozaj chcete obnoviť tému?") == false) {
        return;
    }
    var formData = new FormData();
    formData.append("method", "restoreIvTopic");
    formData.append("id", jQuery(this).attr('data-id'));
    callBackend(formData).then((response) => {
        showSuccessMessage("Téma bola úspešne obnovená.");
        setTimeout(() => {
            window.location.reload();
        }, 2000);
        location.reload();
    }).catch(e => {
        showResultOnFail('', 'Nepodarilo sa obnoviť tému. Skúste znovu alebo kontaktujte admina.');
        console.log(e);
    });
});

jQuery(".delete_iv_materialEmail").on("click", function () {
    if (confirm("Naozaj chcete zmazať email ?") == false) {
        return;
    }
    var deleteButton = jQuery(this);

    var formData = new FormData();
    formData.append("method", "deleteIvMaterialEmail");
    formData.append("iv_materialEmail_id", jQuery(this).attr('data-id'));
    callBackend(formData).then((response) => {
        showSuccessMessage("Email bol úspešne zmazaný.");
        deleteButton.closest('p').remove();
    }).catch(e => {
        showResultOnFail('', 'Nepodarilo sa zmazať email. Skúste znovu alebo kontaktujte admina.');
        console.log(e);
    });
});


jQuery(document).ready(function ($) {

    $(document).on('click', '#iv_email_attachment', function (e) {
        e.preventDefault();
        var methodOnSelect = function () {
            attachment = mediaUploader.state().get('selection').toJSON();
            let bodyEmail = tinymce.get("document_body");
            for (let index = 0; index < attachment.length; index++) {
                console.log(attachment[index].url);
                bodyEmail.execCommand('mceInsertContent', false, '<br><a href="' + attachment[index].url + '" target="_blank">' + attachment[index].title + '<br>');
            }
        }
        invokeMediaUploader(methodOnSelect);
    });

});